<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
declare(strict_types=1);

namespace Bga\Games\SaintPetersburgExpansion;

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
    case USE_GUILD_HALL = 16;
    case TRADING_HOUSE_PLAYER = 17;
    case USE_TRADING_HOUSE = 18;
    case USE_DEBTORS_PRISON = 19;
    case END_GAME = 99;
}
