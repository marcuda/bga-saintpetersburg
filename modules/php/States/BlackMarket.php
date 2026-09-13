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
namespace Bga\Games\SaintPetersburgExpansion\States;

use Bga\GameFramework\SystemException;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\SaintPetersburgExpansion\PickFromDiscardState;
use Bga\Games\SaintPetersburgExpansion\Game;
use Bga\Games\SaintPetersburgExpansion\StateId;

/**
 * This active player state ask a player to choose a card from discard and then buy or add it.
 */
class BlackMarket extends PickFromDiscardState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::BLACK_MARKET,
            description: clienttranslate('Black Market: ${actplayer} must choose a card then take it'),
            descriptionMyTurn: clienttranslate('Black Market: ${you} must choose a card from discard pile'));
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
        $nextState = parent::actAddCard($cardId, $activePlayerId);
        $this->discardBlackMarket();
        return $nextState;
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
        $nextState = parent::actBuyCard($cardId, $activePlayerId, $trade_id);
        $this->discardBlackMarket();
        return $nextState;
    }

    /**
     * Player skips black market (possible only if hand is full and no affordable card is in discard or only special
     * cards are in discard; hand can be full only if the player was having four cards in hand before playing black
     * market and had displaced warehouse).
     * @param int $activePlayerId The active player id.
     * @param array $args The state arguments.
     * @return mixed The next state (NextPlayer or PlayerTurn).
     * @throws SystemException When the black market can not be skept.
     */
    #[PossibleAction]
    function actSkip(int $activePlayerId, array $args)
    {
        if (!$args['_private'][$activePlayerId]['mustSkip']) {
            throw new SystemException('Player can not skip black market as buy or add a card to hand is possible');
        }
        $game = $this->game;
        $card = $this->discardBlackMarket();
        $this->bga->notify->all('skipBlackMarket',
            clienttranslate('${player_name} can not buy any discarded card nor add one to its hand and must skip black market'), [
                'player_id' => $activePlayerId,
                'player_name' => $game->getPlayerNameById($activePlayerId),
                'card_id' => $card['id'],
                'card_idx' => $card['type_arg']
            ]);
        return $game->getNextState();
    }

    private function discardBlackMarket(): array
    {
        $game = $this->game;
        $cards = $game->cards->getCardsInLocation('beingPlayed');
        $card = reset($cards);
        $game->cards->playCard($card['id']);
        $this->bga->notify->all('discardBlackMarket');
        return $card;
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
     * @param array $args The state arguments.
     * @return string The next state.
     * @throws SystemException Should not happen.
     * @throws UserException Should not happen.
     */
    function zombie(int $playerId, array $args)
    {
        // Level 0 zombie: add first possible card to hand else buy first affordable card else skip.
        $moves = $args['_private'][$playerId]['possibleMoves'];
        $move = array_find($moves, fn(array $move) => $move['can_add']);
        if (is_null($move)) {
            $move = array_find($moves, fn(array $move) => $move['can_buy']);
            if (is_null($move)) {
                return $this->actSkip($playerId, $args);
            }
            if ($move['is_trading']) {
                $tradeId = reset($move['trades']);
            } else {
                $tradeId = -1;
            }
            return $this->actBuyCard($move['card_id'], $playerId, $tradeId);
        }
        return $this->actAddCard($move['card_id'], $playerId);
    }
}

