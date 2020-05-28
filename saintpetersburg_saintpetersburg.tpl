{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div id="publisher_msg" style="display: none; margin-bottom: 5px; margin-right: 10px">
    <div class="roundedbox" style="width: 100%;">
        <div class=roundedboxinner">
            <div class="stp_publisher_msg">
                <div class="stp_publisher_icon"></div>
                &nbsp;
                <span>{PUBLISHER_MSG}</span>
                &nbsp;
                <a id="button_publisher_ack" class="action-button bgabutton bgabutton_blue" href="#">{PUBLISHER_ACK}</a>
            </div>
        </div>
    </div>
</div>

<!-- Game board, card stacks and play area -->
<div id="stp_gameboard">
    <div id="discard_pile" class="stp_discard"></div>
    <div id="decks">
        <div id="deck_Worker" class="stp_deck" style="left: 185px; top: 40px; background-position: 0px 0px;"></div>
        <div id="deck_Building" class="stp_deck" style="left: 327px; top: 40px; background-position: -70px 0px;"></div>
        <div id="deck_Aristocrat" class="stp_deck" style="left: 473px; top: 40px; background-position: -140px 0px;"></div>
        <div id="deck_Trading" class="stp_deck" style="left: 617px; top: 40px; background-position: -210px 0px;"></div>
        <div id="deck_counts" class="stp_deckcount">
            <span id="count_Worker" style="left: 230px; top: 128px">0</span>
            <span id="count_Building" style="left: 372px; top: 128px">0</span>
            <span id="count_Aristocrat" style="left: 517px; top: 128px">0</span>
            <span id="count_Trading" style="left: 661px; top: 128px">0</span>
        </div>
    </div>

    <!-- BEGIN square -->
    <div id="square_{X}_{Y}" class="stp_square" style="left: {LEFT}px; top: {TOP}px;"></div>
    <!-- END square -->

    <div id="cards">
    </div>
</div>

<!-- Current player hand -->
<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand"></div>
</div>

<!-- Player tables -->
<div id="playertables">
    <!-- BEGIN player -->
    <div id="playertable_{PLAYER_ID}_wrap" class="whiteblock">
        <h3 style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</h3>
        <div id="playertable_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->
</div>

<!-- Action buttons -->
<div id="button_1"></div>
<div id="button_2"></div>
<div id="button_3"></div>
<div id="button_4"></div>

<script type="text/javascript">

// Javascript HTML templates

// Card on board
var jstpl_card = '<div class="stp_card" id="card_${col}_${row}" style="background-position:-${x}px -${y}px"></div>';

// Additional card elements for cards on table (Observatory)
var jstpl_card_content =
    '<div id="card_content_${id}">' +
        '<div id="card_content_mask_${id}" class="stp_maskcard"></div>' +
        '<div id="card_content_activewrap_${id}" class="stp_clickcard">' +
            '<div id="card_content_active_${id}" class="stp_activecard"><a href="#"></a></div>' +
        '</div>' +
     '</div>';

var jstpl_player_board =
    '<div class="stp_board">' +
        '<img id="handcount_icon_p${id}" class="imgtext" src="${url}/img/hand.png">' +
        '<span id="handcount_p${id}">0</span>' +
        '&nbsp;' +
        '<span id="rublecount_icon_p${id}" class="stp_iconspan">&#8381;</span>' +
        '<span id="rublecount_p${id}">??</span>' +
        '&nbsp;' +
        '<div id="token_wrap_p${id}">' +
            '<div id="token_p${id}" class="imgtext stp_token_small"></div>' +
            '&nbsp;' +
            '<div id="token2_p${id}" class="imgtext stp_token_small"></div>' +
            '&nbsp;' +
        '</div>' +
    '</div>';

var jstpl_card_tooltip =
    '<div class="stp_cardtooltip">' +
        '<h3>${card_name}</h3>' +
        '<hr/>' +
        '<b>${card_type}</b>\<br/>' +
        '${card_text}' +
        '<div>' +
            '<div class="stp_cardart" style="background-position: -${artx}px -${arty}px;"></div>' +
        '</div>' +
        '<i>${card_nbr_label}: ${card_nbr}</i>' +
    '</div>';

var jstpl_hand_tooltip =
    '<div class="stp_cardtooltip">' +
        '<b>${text}</b>' +
        '<div>' +
            '<div class="stp_cardart_small" style="background-position: -${artx.0}px -${arty.0}px; display: ${disp.0}"></div>' +
            '<div class="stp_cardart_small" style="background-position: -${artx.1}px -${arty.1}px; display: ${disp.1}"></div>' +
            '<div class="stp_cardart_small" style="background-position: -${artx.2}px -${arty.2}px; display: ${disp.2}"></div>' +
            '<div class="stp_cardart_small" style="background-position: -${artx.3}px -${arty.3}px; display: ${disp.3}"></div>' +
        '</div>' +
    '</div>';

</script>

{OVERALL_GAME_FOOTER}
