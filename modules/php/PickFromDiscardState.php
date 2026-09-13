<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg The Banquet implementation: © Nicolas Delaporte <nicolas.delaporte+bga@neutralite.org>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburgExpansion;

use Bga\GameFramework\SystemException;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;

/**
 * This active player state ask a player to choose a card from discard and then buy or add it.
 */
class PickFromDiscardState extends CardState
{
    function __construct(protected Game $game, StateId $id, string $description, string $descriptionMyTurn)
    {
        parent::__construct($game, $id, $description, $descriptionMyTurn);
    }

    /**
     * Get the state arguments to be sent to client.
     * Player want to pick a card with debtor’s prison or black market.
     * Return discarded cards details and possible actions.
     * @param int $activePlayerId The active player id.
     * @return array All possible moves.
     * @throws SystemException If the debtor’s prison or black market can not be used.
     */
    function getArgs(int $activePlayerId): array
    {
        $game = $this->game;
        // Get cards pickable with black market:
        $cards = $game->cards->getCardsInLocation('discard');
        if ($cards == null || count($cards) < 1) {
            throw new SystemException("Impossible pick from discard state.");
        }

        $rubles = $game->getRubles($activePlayerId);
        $hand_full = $game->isHandFull($activePlayerId);
        $moves = [];
        foreach ($cards as $card) {
            $moves[$card['id']] = $game->getPossibleMoves($activePlayerId, $card, $rubles, $hand_full, ROW_DISCARD);
        }

        return [
            '_private' => [
                $activePlayerId => [
                    'possibleMoves' => $moves,
                    'mustSkip' => array_all($moves, fn(array $move) => !$move['can_buy'] && !$move['can_add'])
                ]
            ],
            'player_id' => $activePlayerId
        ];
    }

    /**
     * Player adds a card to their hand.
     * @param int $cardId A discarded card id to add to player hand.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer or PlayerTurn).
     * @throws SystemException When the card is not discarded.
     * @throws UserException When player hand is full.
     */
    #[PossibleAction]
    function actAddCard(int $cardId, int $activePlayerId)
    {
        return $this->addCard(ROW_DISCARD, $cardId, $activePlayerId);
    }

    /**
     * Player buys a card.
     * @param int $cardId A discarded card id to buy.
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or -1 if no traded card.
     * @return mixed The next state (NextPlayer or PlayerTurn).
     * @throws SystemException When the card is not discarded or trade is not possible.
     * @throws UserException When player does not have enough rubles.
     */
    #[PossibleAction]
    function actBuyCard(int $cardId, int $activePlayerId, int $trade_id = - 1)
    {
        return $this->buyCard(ROW_DISCARD, $cardId, $activePlayerId, $trade_id);
    }
}

