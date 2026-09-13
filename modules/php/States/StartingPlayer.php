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

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\SaintPetersburgExpansion\Game;
use Bga\Games\SaintPetersburgExpansion\Phase;
use Bga\Games\SaintPetersburgExpansion\StateId;

/**
 * This game state activate the next player.
 */
class StartingPlayer extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::STARTING_PLAYER->value, type: StateType::GAME);
    }
    
    /**
     * Give more time and activate next player
     * @return mixed The next state (PlayerTurn, NextPlayer, ScorePhase).
     */
    function onEnteringState()
    {
        $game = $this->game;
        $currentRound = (int)$game->getGameStateValue('current_phase');
        $phase = Phase::fromRound($currentRound);
        $startingPlayer = (int)$game->getGameStateValue("starting_player_" . $phase->name);
        $this->gamestate->changeActivePlayer($startingPlayer);
        if ($game->dbGetAutoPass($startingPlayer) || !$game->canPlay($startingPlayer)) {
            // Player is auto passing or must pass since no available play
            return $game->passPlayer($startingPlayer, false);
        }
        // Player turn
        $game->giveExtraTime($startingPlayer);
        return PlayerTurn::class;
    }
}

