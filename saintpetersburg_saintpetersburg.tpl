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
            <div class="stp_banner_msg">
                <div class="stp_publisher_icon"></div>
                &nbsp;
                <span>{PUBLISHER_MSG}</span>
                &nbsp;
                <a id="button_publisher_ack" class="action-button bgabutton bgabutton_blue" href="#">{PUBLISHER_ACK}</a>
            </div>
        </div>
    </div>
</div>

<div id="autopass_msg" style="display: none; margin-bottom: 5px; margin-right: 10px">
    <div class="roundedbox" style="width: 100%;">
        <div class=roundedboxinner">
            <div id="autopass" class="stp_banner_msg">
                <span>{AUTOPASS_MSG}</span>
                <a href="#" class="action-button bgabutton bgabutton_red" style="line-height:normal;" onclick="return false;" id="button_cancel_pass">{CANCEL}</a>
            </div>
        </div>
    </div>
</div>

<!-- Game board, card stacks and play area -->
<div id="stp_game_area">
    <div id="stp_gameboard_width_sizer">
        <div id="stp_gameboard_height_sizer">
            <div id="stp_gameboard">
                <div id="discard_pile" class="stp_discard"></div>
                <div id="aristocrat_table" class="stp_aritable"></div>
                <div id="stp_game_board_center">
                    <div id="stp_phase_label" class="stp_label"></div>
                </div>
                <div id="stp_final_label" class="stp_label" style="display: none;">{FINAL}</div>
                <div id="decks">
                    <div id="deck_Worker" class="stp_deck stp_deck_worker"></div>
                    <div id="deck_Building" class="stp_deck stp_deck_building"></div>
                    <div id="deck_Aristocrat" class="stp_deck stp_deck_aristocrat"></div>
                    <div id="deck_Trading" class="stp_deck stp_deck_trading"></div>
                    <div id="deck_counts">
                        <span id="stp_count_Worker" class="stp_label">0</span>
                        <span id="stp_count_Building" class="stp_label">0</span>
                        <span id="stp_count_Aristocrat" class="stp_label">0</span>
                        <span id="stp_count_Trading" class="stp_label">0</span>
                    </div>
                </div>
        
                <!-- BEGIN square -->
                <div id="square_{X}_{Y}" class="stp_square" style="left: {LEFT}%; top: {TOP}%;"></div>
                <!-- END square -->
        
                <div id="stp_cards">
                </div>
            </div>
        </div>
    </div>
    <!-- Current player hand -->
    <div id="stp_myhand_wrap" class="whiteblock">
        <h3>{MY_HAND}</h3>
        <div id="stp_myhand"></div>
    </div>
</div>

<!-- Player tables -->
<div id="stp_playertables">
    <!-- BEGIN player -->
    <div id="stp_playertable_{PLAYER_ID}_wrap" class="whiteblock">
        <h3 style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</h3>
        <div id="stp_playertable_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->
</div>

<!-- Action buttons -->
<div id="button_1"></div>
<div id="button_2"></div>
<div id="button_3"></div>
<div id="button_4"></div>
<div id="button_autopass"></div>

<script type="text/javascript">

// Javascript HTML templates

// Card on board
const jstpl_card = '<div class="stp_card" id="card_${col}_${row}" style="background-position:${x}% ${y}%"></div>';

// Additional card elements for cards on table (Observatory)
const jstpl_card_content =
    '<div id="card_content_${id}">' +
        '<div id="card_content_mask_${id}" class="stp_maskcard"></div>' +
        '<div id="card_content_active_${id}" class="stp_clickcard"></div>' +
     '</div>';

const jstpl_player_board =
    '<div class="stp_board">' +
        '<div id="rublecount_icon_p${id}" class="imgtext stp_icon stp_icon_ruble"></div>' +
        '<span id="rublecount_p${id}">?</span>' +
        '&nbsp;' +
        '<div id="aricount_icon_p${id}" class="imgtext stp_icon stp_icon_aricount"></div>' +
        '<span id="aricount_p${id}">0</span>' +
        '&nbsp;' +
        '<div id="handcount_icon_p${id}" class="imgtext stp_icon stp_icon_hand"></div>' +
        '<span id="handcount_p${id}">0</span>' +
        '&nbsp;' +
        '<div id="cardicon_p${id}_0"></div>' +
        '<div id="cardicon_p${id}_1"></div>' +
        '<div id="cardicon_p${id}_2"></div>' +
        '<div id="cardicon_p${id}_3"></div>' +
        '<br>' +
        '<div id="income_wrap_p${id}">' +
            '<div id="income_icon_rubles_p${id}" class="stp_icon stp_icon_rubles"><span>+</span></div>' +
            '<div id="income_wrap_rubles_p${id}" style="display: inline-block;">' +
                '<span id="income_rubles_p${id}_0">0</span>/' +
                '<span id="income_rubles_p${id}_1">0</span>/' +
                '<span id="income_rubles_p${id}_2">0</span>' +
            '</div>' +
            '<br>' +
            '<div id="income_icon_points_p${id}" class="stp_icon stp_icon_points"><span>+</span></div>' +
            '<div id="income_wrap_points_p${id}" style="display: inline-block;">' +
                '<span id="income_points_p${id}_0">0</span>/' +
                '<span id="income_points_p${id}_1">0</span>/' +
                '<span id="income_points_p${id}_2">0</span>' +
            '</div>' +
        '</div>' +
        '<div id="token_wrap_p${id}" style="margin-left: 15px; position: relative; top: -6px">' +
            '<div id="token_p${id}" class="imgtext stp_token"></div>' +
            '&nbsp;' +
            '<div id="token2_p${id}" class="imgtext stp_token"></div>' +
        '</div>' +
    '</div>';

const jstpl_card_tooltip =
    '<div class="stp_cardtooltip">' +
        '<h3>${card_name}</h3>' +
        '<hr/>' +
        '<b>${card_type}</b>\<br/>' +
        '${card_text}' +
        '<div>' +
            '<div class="stp_cardart" style="background-position: ${artx}% ${arty}%;"></div>' +
        '</div>' +
        '<i>${card_nbr_label}: ${card_nbr}</i>' +
    '</div>';

const jstpl_hand_tooltip =
    '<div class="stp_cardtooltip">' +
        '<b>${text}</b>' +
        '<div>' +
            '<div class="stp_cardart_small" style="background-position: ${artx.0}% ${arty.0}%; display: ${disp.0}"></div>' +
            '<div class="stp_cardart_small" style="background-position: ${artx.1}% ${arty.1}%; display: ${disp.1}"></div>' +
            '<div class="stp_cardart_small" style="background-position: ${artx.2}% ${arty.2}%; display: ${disp.2}"></div>' +
            '<div class="stp_cardart_small" style="background-position: ${artx.3}% ${arty.3}%; display: ${disp.3}"></div>' +
        '</div>' +
    '</div>';

const jstpl_ari_tooltip =
    '<div class="stp_aritooltip">' +
        '<p>${text}</p>' +
        '<table><tbody><tr style="background-color:rgb(252,185,115);">' +
            '<th>${aristocrats}</th>' +
            '<td>1</td>' +
            '<td>2</td>' +
            '<td>3</td>' +
            '<td>4</td>' +
            '<td>5</td>' +
            '<td>6</td>' +
            '<td>7</td>' +
            '<td>8</td>' +
            '<td>9</td>' +
            '<td>10+</td>' +
        '</tr><tr>' +
            '<th>${points}</th>' +
            '<td>1</td>' +
            '<td>3</td>' +
            '<td>6</td>' +
            '<td>10</td>' +
            '<td>15</td>' +
            '<td>21</td>' +
            '<td>28</td>' +
            '<td>36</td>' +
            '<td>45</td>' +
            '<td>55</td>' +
        '</tr></tbody></table>' +
    '</div>';

</script>

{OVERALL_GAME_FOOTER}
