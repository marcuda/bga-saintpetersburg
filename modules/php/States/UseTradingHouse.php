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
use Bga\GameFramework\UserException;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

/**
 * This active player state allow a player having the trading house to buy points.
 */
class UseTradingHouse extends GameState
{
    private const int RUBLES_COST = 3;
    private const int POINTS = 2;

    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::USE_TRADING_HOUSE->value, type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} may choose to use Trading House'),
            descriptionMyTurn: clienttranslate('Trading House: ${you} may buy 2 points for 3 rubles'));
    }
    
    /**
     * Get the state arguments to be sent to client.
     * Player can choose to buy points with Trading House.
     * @param int $activePlayerId The active player id.
     * @return array An array holding for playerId a canBuy flag indicating if the player has enough rubles to
     * buy points.
     */
    function getArgs(int $activePlayerId): array
    {
        $game = $this->game;
        return [
            '_private' => [$activePlayerId => ['canBuy' => ($game->getRubles($activePlayerId) >= self::RUBLES_COST)]]
        ];
    }

    /**
     * Player buys points using Trading House
     * @param int $activePlayerId The active player id.
     * @return string The next state.
     * @throws UserException When player does not have enough rubles.
     */
    #[PossibleAction]
    function actBuyPoints(int $activePlayerId)
    {
        $game = $this->game;
        // A player can buy up to 2 points for 3 rubles.
        $rubles = $game->getRubles($activePlayerId);
        if ($rubles < self::RUBLES_COST) {
            throw new UserException(clienttranslate("You do not have enough rubles"));
        }
        // Update rubles, points, stats
        $game->incRubles($activePlayerId, -self::RUBLES_COST);
        $this->bga->playerStats->inc('rubles_spent', self::RUBLES_COST, $activePlayerId);
        $this->bga->playerScore->inc($activePlayerId, self::POINTS, null);
        $this->bga->playerStats->inc('tradingHousePoints', self::POINTS, $activePlayerId);

        $msg = clienttranslate('${player_name} uses Trading House to buy 2 points for 3 rubles');
        $this->bga->notify->all('buyPoints', $msg, array(
            'player_id' => $activePlayerId,
            'player_name' => $game->getPlayerNameById($activePlayerId),
            'points' => self::POINTS,
            'cost' => self::RUBLES_COST
        ));

        return UsePub::class;
    }

    /**
     * Player declines to use the Trading House.
     * @param int $activePlayerId The active player id.
     * @return string The next state.
     */
    #[PossibleAction]
    function actPass(int $activePlayerId)
    {
        $game = $this->game;
        $this->bga->notify->all('message', clienttranslate('${player_name} declines to use the Trading House'),
            ['player_name' => $game->getPlayerNameById($activePlayerId)]);
        return UsePub::class;
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
     * @param int $playerId The zombie player id.
     * @return string The next state.
     */
    function zombie(int $playerId)
    {
        // Level 0 zombie: do not buy points.
        return $this->actPass($playerId);
    }
}

