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

namespace Bga\Games\SaintPetersburg;

/**
 * Defines each phase of the game.
 */
enum Phase: string
{
    case Worker = 'Worker';
    case Building = 'Building';
    case Aristocrat = 'Aristocrat';
    case Trading = 'Trading';

    static function fromRound(int $round): Phase
    {
        return self::cases()[$round % 4];
    }
}
