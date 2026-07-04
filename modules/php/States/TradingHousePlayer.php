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
namespace Bga\Games\SaintPetersburg\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\Phase;
use Bga\Games\SaintPetersburg\StateId;

/**
 * This game state activate the trading house player.
 */
class TradingHousePlayer extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::TRADING_HOUSE_PLAYER->value, type: StateType::GAME);
    }
    
    /**
     * Activate trading house player
     * @return mixed The next state (UseTradingHouse).
     */
    function onEnteringState()
    {
        $game = $this->game;
        // Next player
        $playerId = $game->getTradingHousePlayer();
        $this->gamestate->changeActivePlayer($playerId);
        return UseTradingHouse::class;
    }
}

