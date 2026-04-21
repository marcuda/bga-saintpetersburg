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

class NextPlayer extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::NEXT_PLAYER->value, type: StateType::GAME);
    }
    
    /**
     * Give more time and activate next player
     * @return mixed The next state (PlayerTurn, NextPlayer, ScorePhase).
     */
    function onEnteringState()
    {
        $game = $this->game;
        // Next player
        $player_id = (int)$game->activeNextPlayer();
        
        // Count one turn when it gets back to the player that started this phase
        $current_phase = $game->getGameStateValue('current_phase') % 4;
        $phase = $game->phases[$current_phase];
        $starting_player = $game->getGameStateValue("starting_player_" . $phase);
        if ($player_id == $starting_player) {
            $this->bga->tableStats->inc('turns_number', 1);
        }
        
        if ($game->dbGetAutoPass($player_id) || !$game->canPlay($player_id)) {
            // Player is auto passing or must pass since no available play
            return $game->passPlayer($player_id, false);
        }
        // Next player turn
        $game->giveExtraTime($player_id);
        return PlayerTurn::class;
    }
}

