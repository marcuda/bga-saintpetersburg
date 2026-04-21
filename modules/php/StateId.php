<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
declare(strict_types=1);

namespace Bga\Games\SaintPetersburg;

/**
 * Defines each state id of the state machine.
 */
enum StateId: int
{
    case PLAYER_TURN = 10;
    case NEXT_PLAYER = 11;
    case SCORE_PHASE = 12;
    case NEXT_PHASE = 13;
    case USE_OBSERVATORY = 14;
    case USE_PUB = 15;
    case END_GAME = 99;
}
