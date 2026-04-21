<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburg\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

class NextPhase extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::NEXT_PHASE->value, type: StateType::GAME, updateGameProgression: true);
    }
    
    /*
     * Progress from completed phase to the next phase/round and
     * track end game trigger
     * @return mixed The next state (PlayerTurn, NextPlayer, ScorePhase).
     */
    function onEnteringState()
    {
        $game = $this->game;
        // Increment phase
        $next_phase = $game->incGameStateValue('current_phase', 1) % 4;
        $phase = $game->phases[$next_phase];
        
        // Clear any automatic passing
        $game->DbQuery("UPDATE player SET autopass=0");
        
        // Handle new round (Trading -> Worker)
        if ($next_phase == 0) {
            // Discard bottom cards and move top row down
            $this->discardBottomRow();
            $this->shiftCardsDown();
            
            // Rotate starting player tokens
            $tokens = array();
            $players = $game->loadPlayersBasicInfos();
            $next_player = $game->createNextPlayerTable(array_keys($players));
            foreach ($game->phases as $token_phase) {
                $token = "starting_player_" . $token_phase;
                $player_id = $game->getGameStateValue($token);
                $game->setGameStateValue($token, $next_player[$player_id]);
                $tokens[$token_phase] = array(
                    'current' => $player_id,
                    'next' => $next_player[$player_id]
                );
            }
            
            // Reset any used Observatory cards
            // and notify players to update score counters
            $obs_players = array();
            for ($i=0; $i<2; $i++) {
                if ($game->getGameStateValue('observatory_' . $i . '_used') == 1) {
                    $card = $game->cards->getCard($game->getGameStateValue('observatory_' . $i . '_id'));
                    $obs_players[] = $card['location_arg'];
                    $game->setGameStateValue('observatory_' . $i . '_used', 0);
                }
            }
            
            $this->bga->tableStats->inc('rounds_number', 1);
            
            $this->bga->notify->all('newRound', "", array(
                'tokens' => $tokens,
                'observatory' => $obs_players,
            ));
        }
        
        // Move all cards on board as far right as possible
        $num_cards = $this->shiftCardsRight();
        
        // Draw up to 8 new cards from current deck
        $new_cards = $game->drawCards(8 - $num_cards, $num_cards, $phase);
        
        // Check if deck was emptied to trigger final round
        if ($game->cards->countCardInLocation('deck_' . $phase) <= 0) {
            if (!$game->getGameStateValue("last_round")) {
                $game->setGameStateValue("last_round", 1);
                $msg = clienttranslate('Final round! ${phase} deck is empty');
                $this->bga->notify->all('lastRound', $msg, array(
                    'i18n' => array('phase'),
                    'phase' => $phase
                ));
            }
        }
        
        // Activate starting player (_not_ next player) for next phase
        $starting_player = (int)$game->getGameStateValue("starting_player_" . $phase);
        $this->gamestate->changeActivePlayer($starting_player);
        $this->bga->tableStats->inc('turns_number', 1);
        
        $msg = clienttranslate('${phase} phase begins, starting with ${player_name}');
        $this->bga->notify->all('nextPhase', $msg, array(
            'i18n' => array('phase'),
            'player_name' => $game->getPlayerNameById($starting_player),
            'phase' => $phase,
            'phase_arg' => $phase, // non-translated arg used in client (i18n came late)
            'cards' => $new_cards
        ));
        
        if ($game->canPlay($starting_player)) {
            $game->giveExtraTime($starting_player);
            return PlayerTurn::class;
        }
        // Player must pass since no available play
        return $game->passPlayer($starting_player, false);
    }
    
    /*
     * Move all cards on the board as far right as possible and return the
     * total number of cards on the board.
     * Cards on the lower row go all the way to the end; those above to the
     * next open position left of any lower cards.
     */
    function shiftCardsRight(): int
    {
        $game = $this->game;
        $num_cards = 0;
        foreach (array(BOTTOM_ROW, TOP_ROW) as $row) {
            $board = $game->cards->getCardsInLocation($row, null, 'location_arg');
            if (count($board) == 0) {
                continue;
            }
            
            // Build associative array of old => new positions for client
            $shifted = array();
            foreach ($board as $card) {
                $loc = $card['location_arg'];
                $shifted[$loc] = $num_cards;
                $game->cards->moveCard($card['id'], $row, $num_cards);
                $num_cards++;
            }
            
            // Row constants are string but client needs integer
            $row_num = ($row == BOTTOM_ROW ? 1 : 0);
            
            $this->bga->notify->all('shiftRight', "", array(
                'columns' => $shifted,
                'row' => $row_num
            ));
        }
        
        return $num_cards;
    }
    
    /*
     * Move all cards on the board from the upper row to the lower
     */
    function shiftCardsDown()
    {
        $game = $this->game;
        $board = $game->cards->getCardsInLocation(TOP_ROW);
        if (count($board) == 0) {
            return;
        }
        
        foreach ($board as $card) {
            $game->cards->moveCard($card['id'], BOTTOM_ROW, $card['location_arg']);
        }
        
        $this->bga->notify->all('shiftDown', "", array(
            'columns' => array_column($board, 'location_arg')
        ));
    }
    
    /*
     * Remove from the game all cards on the board lower row
     */
    function discardBottomRow()
    {
        $game = $this->game;
        $discard = array();
        $board = $game->cards->getCardsInLocation(BOTTOM_ROW);
        foreach ($board as $card) {
            $game->cards->playCard((int)$card['id']);
            $discard[] = array('col' => $card['location_arg'], 'row' => 1);
        }
        
        $this->bga->notify->all('discard', "", array(
            'cards' => $discard
        ));
    }
}

