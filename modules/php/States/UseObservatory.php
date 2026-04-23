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

use Bga\GameFramework\SystemException;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburg\CardState;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

class UseObservatory extends CardState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::USE_OBSERVATORY,
            description: clienttranslate('Observatory: ${actplayer} must take or discard'),
            descriptionMyTurn: clienttranslate('${card_name}: ${you} must take or discard'));
    }
    
    /**
     * Get the state arguments to be sent to client.
     * Player draws a card with Observatory.
     * Return card details and possible actions.
     * @param int $activePlayerId The active player id.
     * @return array All possible moves.
     */
    function getArgs(int $activePlayerId): array
    {
        $game = $this->game;
        // Get card drawn with Observatory
        $cards = $game->cards->getCardsInLocation('obs_tmp', $activePlayerId);
        if ($cards == null || count($cards) != 1) {
            throw new SystemException("Impossible Observatory recall");
        }
        // Possible actions
        $card = array_shift($cards);
        $rubles = $game->getRubles($activePlayerId);
        $hand_full = $game->isHandFull($activePlayerId);
        $possible_moves = $game->getPossibleMoves($activePlayerId, $card, $rubles, $hand_full);
        $obs_id = $game->getGameStateValue("activated_observatory");
        $possible_moves['card'] = $card;
        $possible_moves['obs_id'] = $game->getGameStateValue('observatory_' . $obs_id . '_id');
        $possible_moves['player_id'] = $activePlayerId;
        $possible_moves['i18n'] = array('card_name');
        
        return $possible_moves;
    }
    
    /**
     * Player adds a card to their hand.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer).
     */
    #[PossibleAction]
    function actAddCard(int $activePlayerId)
    {
        return $this->addCard(ROW_OBSERVATORY, 0, $activePlayerId);
    }
    
    /**
     * Player buys a card.
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or -1 if no traded card.
     * @return mixed The next state (NextPlayer).
     */
    #[PossibleAction]
    function actBuyCard(int $activePlayerId, int $trade_id = - 1)
    {
        return $this->buyCard(ROW_OBSERVATORY, 0, $activePlayerId, $trade_id);
    }
    
    /**
     * Player discards the card drawn with Observatory
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer).
     */
    #[PossibleAction]
    function actDiscardCard(int $activePlayerId)
    {
        $game = $this->game;
        // Verify drawn card
        $cards = $game->cards->getCardsInLocation('obs_tmp', $activePlayerId);
        if ($cards == null || count($cards) != 1) {
            throw new SystemException("Impossible Observatory discard");
        }
        
        // Discard
        $card = array_shift($cards);
        $game->cards->playCard((int)$card['id']);
        
        // Reuse end round discard notif arg
        $location = array();
        $location[] = array('row' => ROW_OBSERVATORY);
        
        $msg = clienttranslate('${player_name} discards ${card_name}');
        $this->bga->notify->all('discard', $msg, array(
            'i18n' => array('card_name'),
            'player_name' => $game->getPlayerNameById($activePlayerId),
            'card_name' => $game->getCardName($card),
            'cards' => $location
        ));
        
        // Reset card selection and pass counter globals
        $game->setGameStateValue("activated_observatory", -1);
        $game->setGameStateValue("num_pass", 0);
        $this->bga->playerStats->inc('actions_taken', 1, $activePlayerId);
        return NextPlayer::class;
    }
    
    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     *
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`,
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId)
    {
        // Level 0 zombie: discard card.
        return $this->actDiscardCard($playerId);
    }
}

