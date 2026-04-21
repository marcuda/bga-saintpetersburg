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
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

class UsePub extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::USE_PUB->value, type: StateType::MULTIPLE_ACTIVE_PLAYER,
            description: clienttranslate('Other players may choose to use Pub'),
            descriptionMyTurn: clienttranslate('Pub: ${you} may buy points for 2 Rubles each'));
    }
    
    /**
     * Get the state arguments to be sent to client.
     * Player(s) can choose to buy points with Pub.
     * @return array An array of player(s) that own one or more Pub cards where
     * key: player_id => value: maximum number of points they can buy
     * based on number or Pub cards (1 or 2) and available rubles.
     */
    function getArgs(): array
    {
        $game = $this->game;
        $players = array();
        $pubs = $game->cards->getCardsOfTypeInLocation(PHASE_BUILDING, CARD_PUB, 'table');
        
        // Determine which players own the Pubs
        foreach ($pubs as $card) {
            $player_id = $card['location_arg'];
            if (key_exists($player_id, $players)) {
                $players[$player_id] += 5;
            } else {
                $players[$player_id] = 5;
            }
        }
        
        // Determine available rubles for Pub owner(s)
        foreach ($players as $player_id => $points) {
            $rubles = $game->getRubles($player_id);
            $poss_buys = intdiv($rubles, 2);
            // TODO Rubles are private by default, $poss_buys is indicating the minimum of rubles a player owns
            //   and might even give away a max, it should not be public.
            $players[$player_id] = min($points, $poss_buys);
        }
        
        return $players;
    }
    
    /**
     * Activate multiple player state for any player(s) owning Pub
     */
    function onEnteringState()
    {
        $game = $this->game;
        $player_infos = $game->loadPlayersBasicInfos();
        $player_id = null;
        $pub_players = array();
        
        // Allow any players that own a Pub to use it
        $pubs = $game->cards->getCardsOfTypeInLocation(PHASE_BUILDING, CARD_PUB, 'table');
        foreach ($pubs as $card) {
            if ($player_id == $card['location_arg']) {
                // Same player owns both
                break;
            }
            
            $player_id = (int)$card['location_arg'];
            if ($game->getRubles($player_id) > 0) {
                $pub_players[] = $player_id;
            } else {
                // Player has no money and must pass
                $this->bga->notify->all('message', clienttranslate('${player_name} declines to use the Pub bonus'), array(
                    'player_name' => $player_infos[$player_id]['player_name']
                ));
                // Inform player they passed automatically
                $msg = clienttranslate('You cannot play and were forced to pass automatically');
                $this->notify->player($player_id, 'log', $msg, array());
            }
        }
        
        $this->gamestate->setPlayersMultiactive($pub_players, NextPhase::class, true);
    }
    
    /**
     * Player buys points using a Pub
     * @param int $points The number of bought points.
     * @param int $currentPlayerId The current player id.
     */
    #[PossibleAction]
    function actBuyPoints(#[IntParam(min: 0, max: 10)] int $points, int $currentPlayerId)
    {
        $game = $this->game;
        // A player can buy up to 5 points for 2 rubles each with a Pub,
        // or up to 10 points if the player owns both
        $max_points = 0;
        $pubs = $game->cards->getCardsOfTypeInLocation(
            PHASE_BUILDING, CARD_PUB, 'table');
        foreach ($pubs as $card) {
            if ($card['location_arg'] == $currentPlayerId) {
                $max_points += 5;
            }
        }
        
        // Verify the number of points to buy and also that the current
        // player actually owns at least one Pub
        if ($points < 0 || $points > $max_points) {
            throw new SystemException("Impossible pub buy");
        }
        
        if ($points > 0) {
            // Verify player can pay the cost
            $rubles = $game->getRubles($currentPlayerId);
            $cost = $points * 2;
            if ($cost > $rubles)
                throw new UserException(clienttranslate("You do not have enough rubles"));
                
                // Update rubles, points, stats
                $game->incRubles($currentPlayerId, -$cost);
                $this->bga->playerStats->inc('rubles_spent', $cost, $currentPlayerId);
                $this->bga->playerScore->inc($currentPlayerId, $points, null);
                $this->bga->playerStats->inc('pub_points', $points, $currentPlayerId);
                
                $msg = clienttranslate('${player_name} uses Pub to buy ${points} Point(s) for ${cost} Rubles');
                $this->bga->notify->all('buyPoints', $msg, array(
                    'player_id' => $currentPlayerId,
                    'player_name' => $game->getPlayerNameById($currentPlayerId),
                    'points' => $points,
                    'cost' => $cost
                ));
        } else {
            // No points, skip it
            $this->bga->notify->all('message', clienttranslate('${player_name} declines to use the Pub bonus'), array(
                'player_name' => $game->getPlayerNameById($currentPlayerId)
            ));
        }
        
        // Multiple active state as more than one player can own a Pub
        $this->gamestate->setPlayerNonMultiactive($currentPlayerId, NextPhase::class);
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
        // Level 0 zombie: do not buy points.
        return $this->actBuyPoints(0, $playerId);
    }
}

