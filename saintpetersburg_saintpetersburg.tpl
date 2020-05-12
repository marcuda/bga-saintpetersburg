{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- SaintPetersburg implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div id="board">
    <div id="discard_pile" class="discard"></div>
    <div id="decks">
        <div id="deck_Worker" class="cardback deck" style="left: 175px; top: 30px; background-position: 0px 0px;">0</div>
        <div id="deck_Building" class="cardback deck" style="left: 317px; top: 30px; background-position: -70px 0px;">0</div>
        <div id="deck_Aristocrat" class="cardback deck" style="left: 463px; top: 30px; background-position: -140px 0px;">0</div>
        <div id="deck_Trading" class="cardback deck" style="left: 607px; top: 30px; background-position: -210px 0px;">0</div>
    </div>

	<!-- BEGIN square -->
	<div id="square_{X}_{Y}" class="square" style="left: {LEFT}px; top: {TOP}px;"></div>
	<!-- END square -->

	<div id="cards">
	</div>
</div>

<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand"></div>
</div>

<div id="playertables">
    <!-- BEGIN player -->
    <div id="playertable_{PLAYER_ID}_wrap" class="whiteblock">
	<h3 style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</h3>
	<div id="playertable_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->
</div>

<div id="button_1"></div>
<div id="button_2"></div>
<div id="button_3"></div>
<div id="button_4"></div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_card = '<div class="card" id="card_${col}_${row}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_card_content = '<div id="card_content_${id}">' +
                            '<div id="card_content_${id}_mask" class="maskcard"></div>' +
                            '<div id="card_content_${id}_active" class="activecard"><a href="#">Activate</a></div>' +
                         '</div>';
var jstpl_player_board = '<div class="stp_board">' +
	                     '<div id="rubleicon_p${id}" class="rubleicon nolinebreak"><span id="rublecount_p${id}" class="iconspan"></span></div>&nbsp' + 
	                     '<div id="cardicon_p${id}" class="cardicon nolinebreak"><span id="handcount_p${id}" class="iconspan">0</span></div>&nbsp' + 
                             '<div id="token_wrap_p${id}" style="display: inline-block">' +
	                        '<div id="token_p${id}" class="token"></div>&nbsp' +
	                        '<div id="token2_p${id}" class="token"></div>&nbsp' +
                             '</div>' +
                         '</div>';
var jstpl_card_tooltip = '<div class="cardtooltip">' +
                              '<h3>${card_name}</h3>' +
                              '<hr/>' +
                              '<b>${card_type}</b>\<br/>' +
                              '${card_text}' +
                              '<div class="cardartwrap"><div class="cardart" style="background-position: -${artx}px -${arty}px;"></div></div>' +
                              '<i>${card_nbr_label}: ${card_nbr}</i>' +
                          '</div>';

</script>  

{OVERALL_GAME_FOOTER}
