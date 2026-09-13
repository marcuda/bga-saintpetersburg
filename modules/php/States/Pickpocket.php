<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburgExpansion\States;

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\SaintPetersburgExpansion\Game;
use Bga\Games\SaintPetersburgExpansion\Phase;
use Bga\Games\SaintPetersburgExpansion\StateId;

/**
 * This game state change the current phase.
 */
class Pickpocket extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::PICKPOCKET->value, type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} may use Pickpocket to be the starting player'),
            descriptionMyTurn: clienttranslate('${you} may use Pickpocket to be the starting player'));
    }
    
    /**
     * Get the state arguments to be sent to client.
     * Player must choose rubles or points.
     * @return array An empty array.
     */
    function getArgs(): array
    {
        return [''];
    }

    /**
     * Player uses Pickpocket in order to be the starting player.
     * @param int $activePlayerId The active player id.
     * @return string The next state.
     */
    #[PossibleAction]
    function actPickpocket(int $activePlayerId)
    {
        $game = $this->game;
        $card = $this->getPickpocketCard();
        // Discard:
        $game->cards->playCard($card['id']);

        $this->bga->notify->all('pickpocketUsed',
            clienttranslate('${player_name} use Pickpocket and become the starting player'), [
                'player_id' => $activePlayerId,
                'player_name' => $game->getPlayerNameById($activePlayerId),
                'card_id' => $card['id'],
                'card_idx' => $card['type_arg']
            ]);
        $currentRound = (int)$game->getGameStateValue('current_phase');
        $phase = Phase::fromRound($currentRound);
        $startingPlayer = (int)$game->getGameStateValue("starting_player_" . $phase->name);
        if ($startingPlayer != $activePlayerId) {
            $game->setGameStateValue('starting_player_pickpocket', $activePlayerId);
        }
        if ($game->dbGetAutoPass($activePlayerId) || !$game->canPlay($activePlayerId)) {
            // Player is auto passing or must pass since no available play
            return $game->passPlayer($activePlayerId, false);
        }
        // Player turn
        $game->giveExtraTime($activePlayerId);
        return PlayerTurn::class;
    }

    /**
     * Player declines to use the Pickpocket.
     * @param int $activePlayerId The active player id.
     * @return string The next state.
     */
    #[PossibleAction]
    function actPass(int $activePlayerId)
    {
        $game = $this->game;
        $this->bga->notify->all('message', clienttranslate('${player_name} declines to use the Pickpocket'),
            ['player_name' => $game->getPlayerNameById($activePlayerId)]);
        return StartingPlayer::class;
    }

    /**
     * Gets the Pickpocket card.
     * @return array|null The pickpocket card.
     */
    private function getPickpocketCard(): ?array
    {
        $game = $this->game;
        $cards = $game->cards->getCardsOfTypeInLocation($game->getCardInfos()[CARD_PICKPOCKET]['card_type']->name, CARD_PICKPOCKET, 'hand');
        foreach ($cards as $card) {
            return $card;
        }
        // Should not happen
        return null;
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
     *
     * @param int $playerId The id of the player being a zombie.
     * @return string The next state.
     */
    function zombie(int $playerId)
    {
        // Level 0 zombie: pass
        return $this->actPass($playerId);
    }
}

