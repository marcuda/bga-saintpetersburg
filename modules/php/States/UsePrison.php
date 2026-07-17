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

use Bga\GameFramework\SystemException;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\SaintPetersburgExpansion\CardState;
use Bga\Games\SaintPetersburgExpansion\Game;
use Bga\Games\SaintPetersburgExpansion\StateId;

/**
 * This active player state ask a player to choose a card from discard and then buy add or discard it.
 */
class UsePrison extends CardState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::USE_DEBTORS_PRISON,
            description: clienttranslate('Debtor’s Prison: ${actplayer} must choose a card then take or discard'),
            descriptionMyTurn: clienttranslate('Debtor’s Prison: ${you} must choose a card from discard pile'));
    }

    /**
     * Get the state arguments to be sent to client.
     * Player want to pick a card with debtor’s prison.
     * Return discarded cards details and possible actions.
     * @param int $activePlayerId The active player id.
     * @return array All possible moves.
     * @throws SystemException If the debtor’s prison can not be used..
     */
    function getArgs(int $activePlayerId): array
    {
        $game = $this->game;
        // Get cards pickable with debtor’s prison:
        $cards = $game->cards->getCardsInLocation('discard');
        if ($cards == null || count($cards) < 1) {
            throw new SystemException("Impossible debtor’s prison state.");
        }

        $rubles = $game->getRubles($activePlayerId);
        $hand_full = $game->isHandFull($activePlayerId);
        $moves = [];
        foreach ($cards as $card) {
            $moves[$card['id']] = $game->getPossibleMoves($activePlayerId, $card, $rubles, $hand_full);
        }

        return [
            '_private' => [
                $activePlayerId => [
                    'possibleMoves' => $moves
                ]
            ],
            'player_id' => $activePlayerId
        ];
    }

    /**
     * Player adds a card to their hand.
     * @param int $cardId A discarded card id to add to player hand.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer).
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
     * @return mixed The next state (NextPlayer).
     * @throws SystemException When the card is not discarded or trade is not possible.
     * @throws UserException When player does not have enough rubles.
     */
    #[PossibleAction]
    function actBuyCard(int $cardId, int $activePlayerId, int $trade_id = - 1)
    {
        return $this->buyCard(ROW_DISCARD, $cardId, $activePlayerId, $trade_id);
    }

    /**
     * Player discards the card drawn with debtor’s prison
     * @param int $cardId A discarded card id to discard.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer).
     * @throws SystemException When the card can not be discarded.
     */
    #[PossibleAction]
    function actDiscardCard(int $cardId, int $activePlayerId)
    {
        $game = $this->game;
        // Verify discarded card
        $card = $game->cards->getCard($cardId);
        if ($card == null || $card['location'] != 'discard') {
            throw new SystemException("Impossible prison discard");
        }
        // Move card on top of discard pile.
        $game->cards->insertCardOnExtremePosition($cardId, 'discard', true);
        
        // Reuse end round discard notif arg
        $cards = [[
            'row' => ROW_DISCARD,
            'col' => $card['type_arg']
        ]];
        
        $msg = clienttranslate('${player_name} discards ${card_name}');
        $this->bga->notify->all('discard', $msg, array(
            'i18n' => array('card_name'),
            'player_name' => $game->getPlayerNameById($activePlayerId),
            'card_name' => $game->getCardName($card),
            'cards' => $cards
        ));
        
        // Reset pass counter.
        $game->setGameStateValue("num_pass", 0);
        $this->bga->playerStats->inc('actions_taken', 1, $activePlayerId);
        return NextPlayer::class;
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
     * @throws SystemException Should not happen.
     */
    function zombie(int $playerId, array $args)
    {
        // Level 0 zombie: discard last card.
        return $this->actDiscardCard(array_key_last($args[
        '_private'][$playerId]['possibleMoves']), $playerId);
    }
}

