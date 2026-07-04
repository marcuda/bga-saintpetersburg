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


use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFramework\UserException;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

/**
 * This active player state allow a player having the guild hall to buy points.
 */
class UseGuildHall extends GameState
{
    // Quantity to choose between rubles and points.
    const int QUANTITY = 4;

    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::USE_GUILD_HALL->value, type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must choose how to score the Guild Hall'),
            descriptionMyTurn: clienttranslate('Guild Hall: ${you} must choose rubles and/or points among 4'));
    }
    
    /**
     * Get the state arguments to be sent to client.
     * Player must choose rubles or points.
     * @return array An empty array.
     */
    function getArgs(): array
    {
        return ['toto'];
    }

    /**
     * Player buys points using Trading House
     * @param int $rubles The number of chosen rubles
     * @param int $activePlayerId The active player id.
     * @return string The next state.
     */
    #[PossibleAction]
    function actChoose(#[IntParam(min: 0, max: self::QUANTITY)] int $rubles, int $activePlayerId)
    {
        $game = $this->game;
        $points = self::QUANTITY - $rubles;
        // A player can buy up to 2 points for 3 rubles.

        // Update rubles, points, stats
        if ($points > 0) {
            $score = $this->bga->playerScore->inc($activePlayerId, $points, null);
            $this->bga->playerStats->inc('points_total', $points, $activePlayerId);
            $this->bga->playerStats->inc('points_Building', $points, $activePlayerId);
        } else {
            $score = $this->bga->playerScore->get($activePlayerId);
        }

        if ($rubles > 0) {
            $totalRubles = $game->incRubles($activePlayerId, $rubles);
            $this->bga->playerStats->inc('rubles_total', $rubles, $activePlayerId);
            $this->bga->playerStats->inc('rubles_Building', $rubles, $activePlayerId);
        } else {
            $totalRubles = $game->getRubles($activePlayerId);
        }

        // Using ₽ (ruble symbol) would be anachronic as this symbol was adopted in 2013 (created around 2007) while the
        // game action take place in 1703, so use ruble word.
        $msg = clienttranslate('${player_name} scores ${rubles} rubles and ${points} points for Guild Hall');
        $args = [
            'player_id' => $activePlayerId,
            'player_name' => $game->getPlayerNameById($activePlayerId),
            'points' => $points,
            'rubles' => $rubles,
            'score' => $score
        ];
        if ($game->optShowRubles()) {
            $args['totalRubles'] = $totalRubles;
        } else {
            $args['_private'] = [$activePlayerId => ['totalRubles' => $totalRubles]];
        }
        $this->bga->notify->all('guildHall', $msg, $args);

        return $this->getNextState($activePlayerId);
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
        // Level 0 zombie: do not take rubles (so take 4 points, suit also greedy zombie).
        return $this->actChoose(0, $playerId);
    }

    private function getNextState(int $playerId) {
        $game = $this->game;
        $nextPlayer = $game->getTradingHousePlayer();
        if (is_null($nextPlayer)) {
            return UsePub::class;
        }
        if ($game->getRubles($nextPlayer) > 0) {
            // TODO test zombie having trading house.
            if ($nextPlayer == $playerId) {
                // Already active, can go directly to use trading house state.
                return UseTradingHouse::class;
            }
            // Need a game state to change active player.
            return TradingHousePlayer::class;
        }
        $game->skipTradingHouse($nextPlayer);
        return UsePub::class;
    }
}

