/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * saintpetersburg.js
 *
 * Saint Petersburg user interface.
 */


/*
 * Modified version of (minified) ebg.stock.updateDisplay
 * Horizontally overlaps items of the same type while leaving other unaffected
 *
 * This version excludes some options including vertical overlap and centering items
 *
 * Minified variables renamed where possible to determine something reasonable
 */
const customStockUpdateDisplay = function (from) {
    if (!$(this.control_name)) {
        return;
    }
    const control_box = dojo.marginBox(this.control_name);
    let _item_width = this.item_width;
    if (this.horizontal_overlap != 0) {
        _item_width = Math.round(this.item_width * this.horizontal_overlap / 100);
        zindex = 1;
    }
    let extra_height = 0;
    if (this.vertical_overlap != 0) {
        extra_height = Math.round(this.item_height * this.vertical_overlap / 100) * (this.use_vertical_overlap_as_offset ? 1 : -1);
    }
    let control_width = control_box.w;
    if (this.autowidth) {
        const page_box = dojo.marginBox($("page-content"));
        control_width = page_box.w;
    }
    let item_top = 0;
    let item_left = 0;
    let final_width = 0;
    let n = 0;

    /* BEGIN MOD */
    const dup_item_width = Math.round(this.item_width * this.duplicate_overlap / 100);
    const dup_item_height = Math.round(this.item_height * this.duplicate_overlap / 100);
    let num_dup = 0;
    const item_types = [];
    let zindex = this.duplicate_vertical ? 100 : 1;
    let current_row_height = this.item_height;
    let full_rows_height = 0;

    for (const i in this.items) {
        const item = this.items[i];
        const item_div = this.getItemDivId(item.id);

        // Check for duplicates
        let is_dup = false;
        if (item_types.includes(item.type) && item.type != this.observatory_type) {
            is_dup = true;
            num_dup++;
        } else {
            item_types.push(item.type);
            if (this.duplicate_vertical) num_dup = 0;
        }

        if (this.duplicate_vertical) {
            zindex--;
        } else {
            zindex++;
        }

        // Determine position with duplicate overlap
        if (typeof item.loc == "undefined") {
            if (this.duplicate_vertical) {
                if (is_dup) {
                    n--;
                    current_row_height = Math.max(current_row_height, this.item_height + num_dup * dup_item_height);
                }
                item_left = n * (_item_width + this.item_margin);
                if (item_left + _item_width > control_width) {
                    // Next row, reset all counters
                    n = 0;
                    num_dup = 0;
                    item_left = 0;
                    full_rows_height += current_row_height + this.item_margin;
                    current_row_height = this.item_height;
                }
                final_width = Math.max(final_width, item_left + _item_width);
                item_top = full_rows_height + dup_item_height * num_dup;
            } else { // horizontal overlap
                item_left = n * (_item_width + this.item_margin) - dup_item_width * num_dup;
                if (item_left + _item_width > control_width) {
                    // Next row, reset all counters
                    n = 0;
                    num_dup = 0;
                    item_left = 0;
                    full_rows_height += this.item_height + this.item_margin;
                }
                final_width = Math.max(final_width, item_left + _item_width);
                item_top = full_rows_height;
            }

            n++;
        }

        /* END MOD */
        let item_div_obj = $(item_div);
        if (item_div_obj) {
            if (typeof item.loc == "undefined") {
                dojo.fx.slideTo({
                    node: item_div_obj,
                    top: item_top,
                    left: item_left,
                    duration: 1000,
                    unit: "px"
                }).play();
            } else {
                this.page.slideToObject(item_div_obj, item.loc, 1000).play();
            }
            dojo.style(item_div_obj, "zIndex", zindex);
        } else {
            const type = this.item_type[item.type];
            if (!type) {
                console.error("Stock control: Unknow type: " + type);
            }
            if (typeof item_div == "undefined") {
                console.error("Stock control: Undefined item id");
            } else {
                if (typeof item_div == "object") {
                    console.error("Stock control: Item id with 'object' type");
                    console.error(item_div);
                }
            }
            let additional_style = "";
            if (this.backgroundSize !== null) {
                additional_style += "background-size:" + this.backgroundSize;
            }
            const _115c = dojo.trim(dojo.string.substitute(this.jstpl_stock_item, {
                id: item_div,
                width: this.item_width,
                height: this.item_height,
                top: item_top,
                left: item_left,
                image: type.image,
                position: "z-index:" + zindex,
                extra_classes: this.extraClasses,
                additional_style: additional_style
            }));
            dojo.place(_115c, this.control_name);
            item_div_obj = $(item_div);
            if (typeof item.loc != "undefined") {
                this.page.placeOnObject(item_div_obj, item.loc);
            }
            if (this.selectable == 0) {
                dojo.addClass(item_div_obj, "stockitem_unselectable");
            }
            dojo.connect(item_div_obj, "onclick", this, "onClickOnItem");
            if (type.image_position !== 0) {
                let _115d = 0;
                let _115e = 0;
                if (this.image_items_per_row) {
                    const row = Math.floor(type.image_position / this.image_items_per_row);
                    if (!this.image_in_vertical_row) {
                        _115d = (type.image_position - (row * this.image_items_per_row)) * 100;
                        _115e = row * 100;
                    } else {
                        _115e = (type.image_position - (row * this.image_items_per_row)) * 100;
                        _115d = row * 100;
                    }
                    dojo.style(item_div_obj, "backgroundPosition", "-" + _115d + "% -" + _115e + "%");
                } else {
                    _115d = type.image_position * 100;
                    dojo.style(item_div_obj, "backgroundPosition", "-" + _115d + "% 0%");
                }
            }
            if (this.onItemCreate) {
                this.onItemCreate(item_div_obj, item.type, item_div);
            }
            if (typeof from != "undefined") {
                this.page.placeOnObject(item_div_obj, from);
                if (typeof item.loc == "undefined") {
                    let anim = dojo.fx.slideTo({
                        node: item_div_obj,
                        top: item_top,
                        left: item_left,
                        duration: 1000,
                        unit: "px"
                    });
                    anim = this.page.transformSlideAnimTo3d(anim, item_div_obj, 1000, null);
                    anim.play();
                } else {
                    this.page.slideToObject(item_div_obj, item.loc, 1000).play();
                }
            } else {
                dojo.style(item_div_obj, "opacity", 0);
                dojo.fadeIn({
                    node: item_div_obj
                }).play();
            }
        }
    }
    const final_height = full_rows_height + current_row_height + this.item_margin; /* MOD */
    dojo.style(this.control_name, "height", final_height + "px");
    if (this.autowidth) {
        if (final_width > 0) {
            final_width += (this.item_width - _item_width);
        }
        dojo.style(this.control_name, "width", final_width + "px");
    }
};


define([
        "dojo", "dojo/_base/declare",
        "ebg/core/gamegui",
        "ebg/counter",
        "ebg/stock"
    ],
    function (dojo, declare) {
        'use strict';

        // Board medium width in pixel:
        const BOARD_MEDIUM_WIDTH = 740;
        // Board large width in pixel:
        const BOARD_LARGE_WIDTH = 1400;
        // Board maximum width in pixel:
        const BOARD_MAX_WIDTH = 2712;

        // Card width in pixel when out of board in first edition:
        const CARD_WIDTH_PX = 70;
        // Card height in pixel when out of board in first edition:
        const CARD_HEIGHT_PX = 109;
        // Card width in pixel when out of board in second edition:
        const CARD_WIDTH_PX_2ND = 73.18;
        // Card height in pixel when out of board in second edition:
        const CARD_HEIGHT_PX_2ND = 112;

        // Preference values:
        const PREF_PUBLISHER_MESSAGE = 100;
        const PREF_PM_ON = 0;
        const PREF_PM_OFF = 1;

        const PREF_CARDS_OVERLAP = 101;
        const PREF_CO_HORIZONTAL = 0;
        const PREF_CO_NONE = 1;
        const PREF_CO_VERTICAL = 2;

        const HORIZONTAL_OVERLAP = 60;
        const VERTICAL_OVERLAP = 30;

        const PREF_AUTO_PASS = 102;
        const PREF_AP_NEXT_ACTION = 0;
        const PREF_AP_IMMEDIATELY = 1;

        const PREF_BOARD_SIZE = 103;
        const PREF_BS_AUTO = 0;
        const PREF_BS_SMALL = 1;
        const PREF_BS_MEDIUM = 2;
        const PREF_BS_LARGE = 3;

        const MY_HAND_MAX_WIDTH = 300;
        const MY_HAND_MIN_WIDTH = 150;
        const MY_HAND_MAX_HEIGHT = 260;
        const MY_HAND_MIN_HEIGHT = 145;
        const MY_HAND_PADDING = 20;
        const MY_HAND_MARGIN = 10;

        // Game logic constants
        // Fake column numbers to be used to handle discarded cards.
        const DISCARDED_COL = 0;
        const MOVE_DISCARD_COL = 1;

        return declare("bgagame.saintpetersburg", ebg.core.gamegui, {
            constructor: function () {
                // Enabled console logs if true
                this.debug = false;

                if (this.debug) {
                    console.log('Saint Petersburg constructor');
                }
                this.playerHand = null;         // Stock for current player hand
                this.playerTable = null;        // Stock for current player table
                // Standard card width for stock
                this.cardwidth = CARD_WIDTH_PX;
                // Standard card height for stock
                this.cardheight = CARD_HEIGHT_PX;
                this.card_art_row_size = 10;    // Number of cards per row in sprite for stock
                this.card_art_col_size = 7;     // Number of cards per column in sprite for stock
                this.player_rubles = []         // Counters for all player rubles
                this.discardStock = null        // Discard stock.
                this.player_tables = [];        // Stocks for all player tables
                this.player_hands = [];         // Cards held in each player's hand
                this.player_hand_backs = [];    // Card back types for cards in each player's hand
                this.player_hand_counts = [];   // Counters for all player hands
                this.player_aristocrats = [];   // Counters for all player aristocrats
                this.player_income = [];        // Counters for all player incomes
                this.phases = ['Worker', 'Building', 'Aristocrat', 'Trading']; // Game phases in order
                this.pub_points = 0;            // Current number of points to buy with Pub
                this.max_pub_points = 0;        // Upper limit on Pub points
                this.current_phase = '';        // Current active game phase (string)
                this.card_infos = null;         // Full list of card details
                this.deck_counters = [];        // Counters for cards in each phase stack
                // N.B. terms deck and stack are used interchangeably
                this.client_state_args = {};    // Object to hold argument during client state changes
                this.possible_moves = null;     // All possible moves for current player
                this.constants = null;          // Constant values between client and server
                this.is_trading = false;        // True if in client state for trading card
                this.bga.userPreferences.onChange = (prefId, prefValue) => this.onPreferenceChange(prefId, prefValue);

                this.onSelectCard = this.onSelectCard.bind(this);
            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                if (this.debug) {
                    console.log("Starting game setup", gamedatas);
                }

                if (parseInt(gamedatas.version) === 2) {
                    this.dontPreloadImage('board.jpg');
                    this.dontPreloadImage('cardbacks.jpg');
                    this.dontPreloadImage('cards.jpg');
                    this.dontPreloadImage('icons.jpg');
                    dojo.addClass(dojo.body(), 'stp_2nd_edition');
                    this.card_art_col_size = 6;
                    this.cardwidth = CARD_WIDTH_PX_2ND;
                    this.cardheight = CARD_HEIGHT_PX_2ND;
                } else {
                    this.dontPreloadImage('board2.jpg');
                    this.dontPreloadImage('cardbacks2.jpg');
                    this.dontPreloadImage('cards2.jpg');
                    this.dontPreloadImage('icons2.jpg');
                }

                document.documentElement.style.setProperty('--stp-card-width-px', this.cardwidth + 'px');
                document.documentElement.style.setProperty('--stp-card-height-px', this.cardheight + 'px');

                this.buildBoard(gamedatas);

                if (this.bga.userPreferences.get(PREF_PUBLISHER_MESSAGE) === PREF_PM_ON) {
                    // Show message from publisher player has not seen/acknowledged
                    dojo.style('publisher_msg', 'display', 'block');
                    dojo.connect($('button_publisher_ack'), 'onclick', this, 'ackPublisherMessage');
                }

                // Overlap duplicate cards if preferred
                let duplicate_overlap = 0;
                this.duplicate_vertical = false;
                if (this.bga.userPreferences.get(PREF_CARDS_OVERLAP) === PREF_CO_HORIZONTAL) {
                    duplicate_overlap = HORIZONTAL_OVERLAP;
                } else if (this.bga.userPreferences.get(PREF_CARDS_OVERLAP) === PREF_CO_VERTICAL) {
                    duplicate_overlap = VERTICAL_OVERLAP;
                    this.duplicate_vertical = true;
                }

                // Auto pass banner
                dojo.connect($('button_cancel_pass'), 'onclick', this, 'onCancelAutoPass');
                gamedatas.autopass = parseInt(gamedatas.autopass);
                if (gamedatas.autopass) {
                    // Show banner
                    dojo.style('autopass_msg', 'display', '');
                }

                // Store full card details for tooltips
                // Used in game board setup
                this.card_infos = gamedatas.card_infos;
                this.constants = gamedatas.constants;

                if (gamedatas.newSociety) {
                    this.discardStock = this.createCardStock('stp_discard_stock', 0, duplicate_overlap);
                    this.discardStock.onItemCreate = this.setupNewDiscardedCard.bind(this);
                }

                // Setting up player boards, tables, cards
                for (const player_id in gamedatas.players) {
                    // Custom icons and such
                    const id = parseInt(player_id);
                    this.bga.playerPanels.getElement(id).insertAdjacentHTML('beforeend', `
                        <div class="stp_board">
                            <div id="rublecount_icon_p${id}" class="imgtext stp_icon stp_icon_ruble"></div>
                            <span id="rublecount_p${id}">?</span>&nbsp;
                            <div id="aricount_icon_p${id}" class="imgtext stp_icon stp_icon_aricount"></div>
                            <span id="aricount_p${id}">0</span>&nbsp;
                            <div id="handcount_icon_p${id}" class="imgtext stp_icon stp_icon_hand"></div>
                            <span id="handcount_p${id}">0</span>&nbsp;
                            <div id="cardicon_p${id}_0"></div>
                            <div id="cardicon_p${id}_1"></div>
                            <div id="cardicon_p${id}_2"></div>
                            <div id="cardicon_p${id}_3"></div><br>
                            <div id="income_wrap_p${id}">
                                <div id="income_icon_rubles_p${id}" class="stp_icon stp_icon_rubles"><span>+</span></div>
                                <div id="income_wrap_rubles_p${id}" style="display: inline-block;">
                                    <span id="income_rubles_p${id}_0">0</span>/<span id="income_rubles_p${id}_1">0</span>/<span id="income_rubles_p${id}_2">0</span>
                                </div><br>
                                <div id="income_icon_points_p${id}" class="stp_icon stp_icon_points">
                                    <span>+</span>
                                </div>
                                <div id="income_wrap_points_p${id}" style="display: inline-block;">
                                    <span id="income_points_p${id}_0">0</span>/<span id="income_points_p${id}_1">0</span>/<span id="income_points_p${id}_2">0</span>
                                </div>
                            </div>
                            <div id="token_wrap_p${id}" style="margin-left: 15px; position: relative; top: -6px">
                                <div id="token_p${id}" class="imgtext stp_token"></div>&nbsp;
                                <div id="token2_p${id}" class="imgtext stp_token"></div>
                            </div>
                        </div>`);

                    // Player hand counters
                    const hand_counter = new ebg.counter();
                    hand_counter.create('handcount_p' + player_id);
                    hand_counter.setValue(gamedatas.player_hand_size[player_id]);
                    this.player_hand_counts[player_id] = hand_counter;
                    if (gamedatas.player_hands[player_id] && id !== this.player_id) {
                        // Game option to show player hands enabled
                        // (but no need to do so for current player)
                        this.player_hands[player_id] = [];
                        for (const i in gamedatas.player_hands[player_id]) {
                            this.player_hands[player_id].push(gamedatas.player_hands[player_id][i].type_arg);
                        }
                    } else {
                        // Default just show count of cards in hand
                        this.addTooltip('handcount_p' + player_id, _("Number of cards in hand"), "");
                        this.addTooltip('handcount_icon_p' + player_id, _("Number of cards in hand"), "");
                    }
                    // Card back icons
                    this.player_hand_backs[player_id] = [];
                    for (const i in gamedatas.player_hand_type[player_id]) {
                        this.player_hand_backs[player_id].push(gamedatas.player_hand_type[player_id][i]);
                    }
                    this.updateHandTooltip(player_id);

                    // Player aristocrat counters
                    const ari_counter = new ebg.counter();
                    ari_counter.create('aricount_p' + player_id);
                    ari_counter.setValue(gamedatas.aristocrats[player_id]);
                    this.player_aristocrats[player_id] = ari_counter;
                    this.addTooltip('aricount_p' + player_id, _("Number of different aristocrats"), "");
                    this.addTooltip('aricount_icon_p' + player_id, _("Number of different aristocrats"), "");

                    // Player tables and cards
                    this.player_tables[player_id] = this.createCardStock('stp_playertable_' + player_id, 0, duplicate_overlap);
                    this.player_tables[player_id].onItemCreate = dojo.hitch(this, 'setupNewCard');
                    for (const i in gamedatas.player_tables[player_id]) {
                        const card = gamedatas.player_tables[player_id][i];
                        if (this.debug) {
                            console.log('card', card);
                        }
                        this.player_tables[player_id].addToStockWithId(card.type_arg, card.id);
                    }

                    // Player income
                    // Counters for rubles and points, each an array for Worker/Building/Aristocrat phases
                    this.player_income[player_id] = {'rubles': [], 'points': []};
                    for (let i = 0; i < 3; i++) {
                        this.player_income[player_id].rubles[i] = new ebg.counter();
                        this.player_income[player_id].rubles[i].create('income_rubles_p' + player_id + '_' + i);
                        this.player_income[player_id].rubles[i].setValue(gamedatas.income[player_id].rubles[i]);
                        this.addTooltip('income_icon_rubles_p' + player_id, _("Number of rubles earned in each phase"), "");
                        this.addTooltip('income_wrap_rubles_p' + player_id, _("Number of rubles earned in each phase"), "");

                        this.player_income[player_id].points[i] = new ebg.counter();
                        this.player_income[player_id].points[i].create('income_points_p' + player_id + '_' + i);
                        this.player_income[player_id].points[i].setValue(gamedatas.income[player_id].points[i]);
                        this.addTooltip('income_icon_points_p' + player_id, _("Number of points scored in each phase"), "");
                        this.addTooltip('income_wrap_points_p' + player_id, _("Number of points scored in each phase"), "");
                    }

                    // Rubles (default hidden for other players)
                    if (player_id in gamedatas.rubles) {
                        this.player_rubles[player_id] = new ebg.counter();
                        this.player_rubles[player_id].create('rublecount_p' + player_id);
                        this.player_rubles[player_id].setValue(gamedatas.rubles[player_id]);
                        this.addTooltip('rublecount_p' + player_id, _("Number of rubles"), "");
                        this.addTooltip('rublecount_icon_p' + player_id, _("Number of rubles"), "");
                    } else {
                        this.addTooltip('rublecount_p' + player_id, _("Number of rubles (secret)"), "");
                        this.addTooltip('rublecount_icon_p' + player_id, _("Number of rubles (secret)"), "");
                    }
                }

                // Passed players
                // Game doesn't track specific players to have to work back from active player
                let idx;
                const num_players = gamedatas.players_in_order.length;
                // Find active player in current order
                for (const i in gamedatas.players_in_order) {
                    if (gamedatas.players_in_order[i] == gamedatas.gamestate.active_player) {
                        idx = parseInt(i) + num_players; // pad value so it can decrement and stay positive
                        break;
                    }
                }
                // Mark previous players passed
                for (let i = 0; i < parseInt(gamedatas.num_pass); i++) {
                    idx -= 1;
                    this.disablePlayerPanel(gamedatas.players_in_order[idx % num_players]);
                }

                // Set up player table unless spectating
                this.playerTable = this.player_tables[this.player_id];
                if (this.playerTable !== undefined) { // no table for spectator
                    dojo.connect(this.playerTable, 'onChangeSelection', this, 'onPlayerTableSelectionChanged');
                }

                // Staring player tokens
                this.setTokens(gamedatas.tokens, false);

                // Phase card stacks
                this.setPhase(gamedatas.phase);
                for (const deck in gamedatas.decks) {
                    if (deck.startsWith('deck_')) {
                        dojo.connect($(deck), 'onclick', this, 'onClickDeck');
                        const phase = deck.split('_')[1];
                        this.deck_counters[phase] = new ebg.counter();
                        this.deck_counters[phase].create('stp_count_' + phase);
                        this.deck_counters[phase].setValue(gamedatas.decks[deck]);
                        this.setDeckTooltip(phase, parseInt(gamedatas.decks[deck]));
                    }
                }
                if (gamedatas.last_round) {
                    dojo.style('stp_final_label', 'display', 'block');
                }

                // If a stack is empty it will not be included in gamedatas.decks
                // Ensure the elements are created and set cards to zero
                for (const i in this.phases) {
                    const phase = this.phases[i];
                    if (this.deck_counters[phase] === undefined) {
                        // No counter created means deck is empty
                        dojo.addClass('deck_' + phase, 'stp_emptydeck')
                        dojo.style('stp_count_' + phase, 'color', 'red');
                        this.setDeckTooltip(phase, 0);
                        // Counter shouldn't be needed but create it just in case
                        this.deck_counters[phase] = new ebg.counter();
                        this.deck_counters[phase].create('stp_count_' + phase);
                        this.deck_counters[phase].setValue(0);
                    }
                }

                // Aristocrat table helper tooltip
                const text = _('Players score end game bonus points for each different type of Aristocrat they own, which is tracked in the player panel.');

                if (gamedatas.newSociety) {
                    this.addTooltipHtml('aristocrat_table', `
                    <div class="stp_aritooltip">
                        <p>${text}</p>
                        <table><tbody><tr style="background-color:rgb(252,185,115);">
                            <th>${_('Unique Aristocrats')}</th>
                            <td>1</td>
                            <td>2</td>
                            <td>3</td>
                            <td>4</td>
                            <td>5</td>
                            <td>6</td>
                            <td>7</td>
                            <td>8</td>
                            <td>9</td>
                            <td>10</td>
                            <td>\>10</td>
                        </tr><tr>
                            <th>${_('Bonus Points')}</th>
                            <td>1</td>
                            <td>3</td>
                            <td>6</td>
                            <td>10</td>
                            <td>15</td>
                            <td>21</td>
                            <td>28</td>
                            <td>36</td>
                            <td>45</td>
                            <td>55</td>
                            <td>${_('+10 × others unique')}</td>
                        </tr></tbody></table>
                    </div>`);
                } else {
                    this.addTooltipHtml('aristocrat_table', `
                    <div class="stp_aritooltip">
                        <p>${text}</p>
                        <table><tbody><tr style="background-color:rgb(252,185,115);">
                            <th>${_('Unique Aristocrats')}</th>
                            <td>1</td>
                            <td>2</td>
                            <td>3</td>
                            <td>4</td>
                            <td>5</td>
                            <td>6</td>
                            <td>7</td>
                            <td>8</td>
                            <td>9</td>
                            <td>10+</td>
                        </tr><tr>
                            <th>${_('Bonus Points')}</th>
                            <td>1</td>
                            <td>3</td>
                            <td>6</td>
                            <td>10</td>
                            <td>15</td>
                            <td>21</td>
                            <td>28</td>
                            <td>36</td>
                            <td>45</td>
                            <td>55</td>
                        </tr></tbody></table>
                    </div>`);
                }
                // Player hand (no hand for spectator)
                if (!this.bga.players.isCurrentPlayerSpectator()) {
                    this.playerHand = this.createCardStock('stp_myhand', 1, 0);
                    this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
                    for (const i in gamedatas.player_hands[this.player_id]) {
                        const card = gamedatas.player_hands[this.player_id][i];
                        this.playerHand.addToStockWithId(card.type_arg, card.id);
                    }
                    dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');
                } else {
                    // Hide player hand area for spectators
                    dojo.style('stp_myhand_wrap', 'display', 'none');
                    // Re-center game board
                    dojo.style('stp_gameboard', 'margin', 'auto');
                }

                // Game board cards
                if (this.debug) {
                    console.log('board_top', gamedatas.board_top);
                }
                for (const i in gamedatas.board_top) {
                    const card = gamedatas.board_top[i];
                    this.addCardOnBoard(0, card.location_arg, parseInt(card.type_arg));
                }
                for (const i in gamedatas.board_bottom) {
                    const card = gamedatas.board_bottom[i];
                    this.addCardOnBoard(1, card.location_arg, parseInt(card.type_arg));
                }

                // Observatory status
                for (const i in gamedatas.observatory) {
                    const card = gamedatas.observatory[i];
                    if (parseInt(card.used) === 1) {
                        // Mask card to show it is used
                        dojo.style('card_content_mask_' + card.id, 'display', 'block');
                    }
                }

                if (gamedatas.newSociety) {
                    // Debtor’s Prison status.
                    const prisonData = gamedatas.prison;
                    if (parseInt(prisonData.used) === 1) {
                        // Mask card to show it is used
                        dojo.style('card_content_mask_' + prisonData.id, 'display', 'block');
                    }
                }

                // Discard pile
                if (gamedatas.lastDiscarded !== null) {
                    this.addCardOnBoard(this.constants.discardRow, DISCARDED_COL, parseInt(gamedatas.lastDiscarded.type_arg));
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                if (this.debug) {
                    console.log("Ending game setup");
                }
            },

            getCSSVariable: function(name) {
                return getComputedStyle(document.documentElement).getPropertyValue(name);
            },

            buildBoard: function (gameData) {
                if (this.debug) {
                    console.log("buildBoard");
                }
                const PUBLISHER_MSG = dojo.string.substitute(
                    _('A word from ${publisherName}: the artwork for Saint Petersburg is being reworked and this temporary version will be replaced when the new artwork is ready.'),
                    {publisherName: 'Hans im Glück'});
                this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
                    <div id="publisher_msg" style="display: none; margin-bottom: 5px; margin-right: 10px">
                        <div class="roundedbox" style="width: 100%;">
                            <div class=roundedboxinner">
                                <div class="stp_banner_msg">
                                    <div class="stp_publisher_icon"></div>
                                    &nbsp;
                                    <span>${PUBLISHER_MSG}</span>
                                    &nbsp;
                                    <a id="button_publisher_ack" class="action-button bgabutton bgabutton_blue" href="#">${_('Okay, got it!')}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="autopass_msg" style="display: none; margin-bottom: 5px; margin-right: 10px">
                        <div class="roundedbox" style="width: 100%;">
                            <div class=roundedboxinner">
                                <div id="autopass" class="stp_banner_msg">
                                    <span>${_('You will automatically pass your turn until the next phase begins!')}</span>
                                    <a href="#" class="action-button bgabutton bgabutton_red" style="line-height:normal;" onclick="return false;" id="button_cancel_pass">${_('Cancel')}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game board, card stacks and play area -->
                    <div id="stp_game_area">
                        <div id="stp_discard_stock_container" class="whiteblock">
                            <h3>${_('Discarded cards')}</h3>
                            <div id="stp_discard_stock"></div>
                        </div>
                        <div id="stp_gameboard_width_sizer">
                            <div id="stp_gameboard_height_sizer">
                                <div id="stp_gameboard">
                                    <div id="discard_pile" class="stp_discard"></div>
                                    <div id="aristocrat_table" class="stp_aritable"></div>
                                    <div id="stp_phase_label" class="stp_label"></div>
                                    <div id="stp_final_label" class="stp_label" style="display: none;">${_('FINAL ROUND')}</div>
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
                            
                                    <!-- Squares -->
                                    
                                    <div id="stp_cards">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Current player hand -->
                        <div id="stp_myhand_wrap" class="whiteblock">
                            <h3>${_('My hand')}</h3>
                            <div id="stp_myhand"></div>
                        </div>
                    </div>

                    <div id="stp_playertables">
                        <!-- Player's tables -->
                    </div>

                    <!-- Action buttons -->
                    <div id="button_1"></div>
                    <div id="button_2"></div>
                    <div id="button_3"></div>
                    <div id="button_4"></div>
                    <div id="button_autopass"></div>
                `);

                // Hide discard stock.
                document.getElementById("stp_discard_stock_container").style.display = 'none';

                // % of board width:
                let horStep = 12.16;
                let hor_padding = 2.7;
                // % of board height:
                let verStep = 25;
                let ver_padding = 48.13;
                if (parseInt(gameData.version) === 2) {
                    horStep = 12.35;
                    hor_padding = 2.2;
                    // % of board height:
                    verStep = 24.81;
                    ver_padding = 37.495;
                }
                const cards = document.getElementById('stp_cards');
                for (let y = 0; y < 2; y++) {
                    for (let x = 0; x < 8; x++) {
                        // Count right to left.
                        const left = (7 - x) * horStep + hor_padding;
                        const top = y * verStep + ver_padding;
                        cards.insertAdjacentHTML('beforebegin',
                            `<div id="square_${x}_${y}" class="stp_square" style="left: ${left}%; top: ${top}%;"></div>`);
                    }
                }

                const playerTables = document.getElementById('stp_playertables');
                // Template block for player boards
                // Get correct order relative to current player
                for (const playerId of gameData.players_in_order) {
                    if (this.debug) {
                        console.log('playerId', playerId);
                    }
                    const player = gameData.players[playerId];
                    playerTables.insertAdjacentHTML('beforeend', `
                        <div id="stp_playertable_${playerId}_wrap" class="whiteblock">
                            <h3 style="color:#${player.color}">${player.name}</h3>
                            <div id="stp_playertable_${playerId}"></div>
                        </div>`);
                }

            },

            onPreferenceChange: function(prefId, prefValue) {
                if (this.debug) {
                    console.log("Preference changed", prefId, prefValue, this.bga.userPreferences.get(prefId));
                }

                switch (prefId) {
                    case PREF_BOARD_SIZE:
                        this.onChangePrefBoardSize(prefValue);
                        break;
                    case PREF_CARDS_OVERLAP:
                        this.onChangePrefOverlap(prefValue);
                        break;
                    case PREF_AUTO_PASS:
                        // Nothing to do.
                        break;
                    default:
                        if (this.debug) {
                            console.log('Preference is not handled: ', prefId);
                        }
                        break;
                }
            },

            onChangePrefBoardSize: function (prefValue) {
                switch (prefValue) {
                    case PREF_BS_AUTO:
                        this.adaptInterface();
                        break;
                    case PREF_BS_SMALL:
                        // interface_min_width is the min value of game_interface_width defined in gameinfos.inc.php.
                        this.setBoardWidth(this.interface_min_width);
                        break;
                    case PREF_BS_MEDIUM:
                        this.setBoardWidth(BOARD_MEDIUM_WIDTH);
                        break;
                    case PREF_BS_LARGE:
                        this.setBoardWidth(BOARD_LARGE_WIDTH);
                        break;
                    default:
                        if (this.debug) {
                            console.log('Board size preference value is not handled: ', prefValue);
                        }
                        break;
                }
            },

            onChangePrefOverlap: function (prefValue) {
                let overlap = 0;
                switch (prefValue) {
                    case PREF_CO_NONE:
                        this.duplicate_vertical = false;
                        break;
                    case PREF_CO_HORIZONTAL:
                        this.duplicate_vertical = false;
                        overlap = HORIZONTAL_OVERLAP;
                        break;
                    case PREF_CO_VERTICAL:
                        this.duplicate_vertical = true;
                        overlap = VERTICAL_OVERLAP;
                        break;
                    default:
                        if (this.debug) {
                            console.log('Card overlap preference value is not handled: ', prefValue);
                        }
                        return;
                }
                if (typeof this.player_tables != "undefined") {
                    for (const playerId in this.player_tables) {
                        const board = this.player_tables[playerId];
                        board.duplicate_overlap = overlap;
                        board.duplicate_vertical = this.duplicate_vertical;
                        board.updateDisplay();
                    }
                    if (this.discardStock !== null) {
                        this.discardStock.duplicate_overlap = overlap;
                        this.discardStock.duplicate_vertical = this.duplicate_vertical;
                        this.discardStock.updateDisplay();
                    }
                }
            },

            setBoardWidth: function(boardWidth) {
                if (this.debug) {
                    console.log('setBoardWidth', boardWidth);
                }
                document.documentElement.style.setProperty('--stp-board-size', boardWidth + 'px');

                this.adaptMyHand();
            },

            adaptMyHand: function () {
                // Adapt my hand depending on play area size:
                const playArea = this.bga.gameArea.getElement();
                const availWidth = playArea.clientWidth;
                const boardWidth = parseInt(this.getCSSVariable('--stp-board-size'));

                const boardHeightToWidthRatio = parseFloat(this.getCSSVariable('--stp-board-height-to-width')) / 100;
                const boardHeight = boardWidth * boardHeightToWidthRatio;

                const availWidthForMyHand = availWidth - boardWidth;
                if (availWidthForMyHand >= MY_HAND_MAX_WIDTH + MY_HAND_PADDING + MY_HAND_MARGIN) {
                    document.documentElement.style.setProperty('--stp-my-hand-width', MY_HAND_MAX_WIDTH + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-height', MY_HAND_MIN_HEIGHT + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-top', 'auto');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-left', MY_HAND_MARGIN + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-right', 'auto');
                } else if ((availWidthForMyHand >= MY_HAND_MIN_WIDTH + MY_HAND_PADDING + MY_HAND_MARGIN) && boardHeight >= MY_HAND_MAX_HEIGHT) {
                    document.documentElement.style.setProperty('--stp-my-hand-width', MY_HAND_MIN_WIDTH + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-height', MY_HAND_MAX_HEIGHT + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-top', 'auto');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-left', MY_HAND_MARGIN + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-right', 'auto');
                } else {
                    // Must put my hand below board.
                    document.documentElement.style.setProperty('--stp-my-hand-width', '100%');
                    document.documentElement.style.setProperty('--stp-my-hand-height', MY_HAND_MIN_HEIGHT + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-top', MY_HAND_MARGIN + 'px');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-left', 'unset');
                    document.documentElement.style.setProperty('--stp-my-hand-margin-right', 'unset');
                }
                if (this.debug) {
                    console.log('Available width for my hand: ', availWidthForMyHand);
                }
            },

            onScreenWidthChange: function () {
                if (this.debug) {
                    console.log('onScreenWidthChange');
                }
                if (this.bga.userPreferences.get(PREF_BOARD_SIZE) === PREF_BS_AUTO) {
                    this.adaptInterface();
                } else {
                    this.adaptMyHand();
                }
            },

            adaptInterface: function () {
                if (this.debug) {
                    console.log('adaptInterface');
                }
                if (this.bga.userPreferences.get(PREF_BOARD_SIZE) !== PREF_BS_AUTO) {
                    // Size is fixed, do nothing.
                    return;
                }

                if (document.getElementById('stp_game_area') === null) {
                    if (this.debug) {
                        console.log('adaptInterface: interface is not yet built.');
                    }
                    return;
                }
                // Adapt interface depending on play area size:
                const playArea = this.bga.gameArea.getElement();
                const availWidth = playArea.clientWidth;

                const getTotalHeight = (nodeId) => {
                    return document.getElementById(nodeId).offsetHeight + parseInt(dojo.style($(nodeId), 'margin-top'))
                        + parseInt(dojo.style($(nodeId), 'margin-bottom'));
                };

                const availHeight = window.innerHeight - (getTotalHeight('topbar') + getTotalHeight('page-title'));
                if (this.debug) {
                    console.log('Available space', availWidth, availHeight, this.interface_min_width);
                }

                const boardHeightToWidthRatio = parseFloat(this.getCSSVariable('--stp-board-height-to-width')) / 100;
                const playerTableHeight = document.getElementById(`stp_playertable_${this.gamedatas.players_in_order[0]}_wrap`).clientHeight;

                const boardMaxHeight = availHeight - playerTableHeight;
                const boardMaxWidth = Math.min(BOARD_MAX_WIDTH, availWidth, Math.max(this.interface_min_width, boardMaxHeight / boardHeightToWidthRatio));

                let myHandWidth = MY_HAND_MIN_WIDTH;
                let myHandHeight = MY_HAND_MAX_HEIGHT;
                if (boardMaxHeight < MY_HAND_MAX_HEIGHT + MY_HAND_PADDING) {
                    myHandWidth = MY_HAND_MAX_WIDTH;
                    myHandHeight = MY_HAND_MIN_HEIGHT;
                }
                let boardWidth = boardMaxWidth;
                const boardMaxWidthWithHandAtSide = availWidth - (myHandWidth + MY_HAND_PADDING + MY_HAND_MARGIN);
                const boardHeightWithHandAtSide = boardMaxWidthWithHandAtSide * boardHeightToWidthRatio;
                if (boardMaxWidthWithHandAtSide >= this.interface_min_width && boardHeightWithHandAtSide >= (myHandHeight + MY_HAND_PADDING)) {
                    // Set board width to a size allowing my hand at its side.
                    boardWidth = Math.min(boardMaxWidth, boardMaxWidthWithHandAtSide);
                }
                if (this.debug) {
                    console.log(`Player cards height: ${playerTableHeight}; board max height: ${boardMaxHeight}; board width: ${boardWidth}.`);
                }

                this.setBoardWidth(boardWidth);
            },

            isAutoPassImmediate: function () {
                if (this.debug) {
                    console.log('isAutoPassImmediate', this.bga.userPreferences.get(PREF_AUTO_PASS));
                }
                return this.bga.userPreferences.get(PREF_AUTO_PASS) === PREF_AP_IMMEDIATELY;
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                if (this.debug) {
                    console.log(`Entering state: “${stateName}”`, args);
                }

                switch (stateName) {
                    case 'PlayerTurn':
                        if (this.gamedatas.buyOnly) {
                            if (this.isCurrentPlayerActive()) {
                                this.bga.statusBar.setTitle(_('${you} must choose a card'));
                            } else {
                                if (this.debug) {
                                    console.log(this.getActivePlayerId());
                                }
                                this.bga.statusBar.setTitle(_('${actplayer} must choose a card'));
                            }
                        }
                        this.client_state_args = {};
                        if (this.isCurrentPlayerActive()) {
                            this.possible_moves = args.args._private.possibleMoves;
                            this.setSelections();
                        } else {
                            this.possible_moves = {};
                        }
                        break;
                    case 'client_tradeCard':
                        this.is_trading = true;
                    // fallthrough
                    case 'client_playCard':
                    // fallthrough
                    case 'client_selectCard':
                        this.setSelections();
                        break;
                    case 'client_useObservatory':
                        // Highlight decks for selection
                        // Cannot select if empty or last card
                        for (const i in this.phases) {
                            const phase = this.phases[i];
                            if (this.deck_counters[phase].getValue() > 1) {
                                dojo.addClass('deck_' + phase, 'stp_selectable');
                            }
                        }
                        break;
                    case 'UseObservatory':
                        this.possible_moves = {};
                        this.possible_moves[this.constants.observatory] = {};
                        if (this.isCurrentPlayerActive()) {
                            this.possible_moves[this.constants.observatory][0] = args.args._private.possibleMoves;
                        } else {
                            args.args._private = { possibleMoves: { cost: -1 }};
                        }
                        this.client_state_args = {
                            row: this.constants.observatory,
                            col: 0
                        };
                        this.showObservatoryChoice(args.args.obs_id, args.args.card, args.args._private.possibleMoves.cost);
                        break;
                    case 'UsePrison':
                        // Only active player is seeing discard pile.
                        if (this.isCurrentPlayerActive()) {
                            this.possible_moves = {};
                            this.possible_moves[this.constants.discardStock] = args.args._private.possibleMoves;
                            this.showDiscardStock(args.args._private.possibleMoves);
                            this.setSelections();
                        }
                        break;
                    case 'UsePub':
                        // Pub negates auto pass
                        dojo.style('autopass_msg', 'display', 'none');
                        if (args.args._private === undefined) {
                            // Current player does not have a pub (but someone else does).
                            this.max_pub_points = 0;
                        } else {
                            this.max_pub_points = args.args._private.maxPoints;
                        }
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                if (this.debug) console.log('Leaving state: ' + stateName);

                // Reset selections for all items
                // No special handling for any state
                // Spectator has no hand or board.
                if (!this.bga.players.isCurrentPlayerSpectator()) {
                    this.playerHand.unselectAll();
                    this.playerTable.setSelectionMode(0);
                }
                dojo.query('.stp_selected').removeClass('stp_selected');
                dojo.query('.stp_selectable').removeClass('stp_selectable');
                this.is_trading = false;
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                if (this.debug) {
                    console.log(`onUpdateActionButtons: “${stateName}”`, args);
                }

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'PlayerTurn':
                            // Options: observatory?, debtor’s prison?, pass
                            if (args._private.possibleMoves[this.constants.observatory].length === undefined) {
                                // args is possible moves and will have an object, which has no length,
                                // for Observatory if valid, otherwise it will be an empty array (length == 0)
                                this.addActionButton("button_1", _("Observatory"), "onButtonObservatory");
                            }
                            if (args._private.possibleMoves[this.constants.prison].length === undefined) {
                                // args is possible moves and will have an object, which has no length,
                                // for prison if valid, otherwise it will be an empty array (length == 0)
                                this.addActionButton("button_2", _("Debtor’s Prison"), "onButtonPrison");
                            }
                            if (!this.gamedatas.buyOnly) {
                                this.addActionButton("button_3", _("Pass"), "onPass");
                                if (!this.gamedatas.autopass) {
                                    this.addActionButton("button_autopass", _("Enable auto pass"), "onAutoPass", null, false, "red");
                                }
                            }
                            break;
                        case 'client_selectCard': {
                            // Options: buy, add, discard?, cancel
                            this.addBuyButton(args, 'onBuyCard');
                            const add_color = args.can_add ? "blue" : "gray";
                            if (!this.gamedatas.buyOnly) {
                                this.addActionButton("button_2", _("Add to hand"), "onAddCard", null, false, add_color);
                            }
                            if (this.possible_moves[this.constants.discardStock] !== undefined) {
                                this.addActionButton("button_3", _("Discard"), "onDiscardCard");
                            }
                            this.addActionButton("button_4", _("Cancel"), "onCancelCard", null, false, "red");
                            break;
                        }
                        case 'client_playCard': {
                            // Options: buy, cancel
                            this.addBuyButton(args, 'onPlayCard');
                            this.addActionButton("button_2", _("Cancel"), "onCancelCard", null, false, "red");
                            break;
                        }
                        case 'client_tradeCard':
                            // Options: cancel
                            this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
                            break;
                        case 'UsePub': {
                            // Options: -1, +1, buy
                            let color = "blue";
                            if (parseInt(args._private.maxPoints[this.player_id]) === 0) {
                                color = "gray";
                            }
                            this.addActionButton("button_1", "-1", "onOneLessPoint", null, false, "gray");
                            this.addActionButton("button_2", "+1", "onOneMorePoint", null, false, color);
                            this.addActionButton("button_3", _("Buy") + " " + this.pub_points + " (" + this.pub_points * 2 + ")", "onBuyPoints");
                            break;
                        }
                        case 'UseTradingHouse': {
                            // Options: buy, pass
                            let color = "blue";
                            if (!args._private.canBuy) {
                                color = "gray";
                            }
                            this.addActionButton("button_1", _("Buy"), "onTradingHouseBuyPoints", null, false, color);
                            this.addActionButton("button_2", _("Pass"), "onTradingHousePass");
                            break;
                        }
                        case 'UseGuildHall': {
                            // Options: 4 rubles, 3 rubles 1 point, 2 rubles 2 points, 1 ruble 3 points, 4 points.
                            // Using ₽ (ruble symbol) would be anachronic as this symbol was adopted in 2013 (created
                            // around 2007) while the game action take place in 1703.
                            this.addActionButton("button_1", _("4 rubles"), "onGuildHall4R");
                            this.addActionButton("button_2", _("3 rubles 1 point"), "onGuildHall3R1P");
                            this.addActionButton("button_3", _("2 rubles 2 points"), "onGuildHall2R2P");
                            this.addActionButton("button_4", _("1 ruble 3 points"), "onGuildHall1R3P");
                            this.addActionButton("button_5", _("4 points"), "onGuildHall4P");
                            break;
                        }
                        case 'client_useObservatory':
                            // Options: cancel
                            this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
                            break;
                        case 'UseObservatory': {
                            const card = args._private.possibleMoves;
                            // Options: buy, add, cancel
                            this.addBuyButton(card, 'onBuyCard');
                            const addColor = card.can_add ? "blue" : "gray";
                            this.addActionButton("button_2", _("Add to hand"), "onAddCard", null, false, addColor);
                            this.addActionButton("button_3", _("Discard"), "onDiscardCard");
                            break;
                        }
                    }
                } else if (!this.gamedatas.buyOnly && stateName === 'PlayerTurn'
                    && !this.gamedatas.autopass && !this.bga.players.isCurrentPlayerSpectator()) {
                    this.addActionButton("button_autopass", _("Enable auto pass"), "onAutoPass", null, false, "red");
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can define some utility methods that you can use everywhere in your JavaScript
                script.
            
            */

            addBuyButton: function(cardData, callback) {
                // Options: buy, add, cancel
                const buyColor = cardData.can_buy ? "blue" : "gray";
                const buyText = dojo.string.substitute(cardData.is_trading ? _("Buy (${cost} - ?)") : _("Buy (${cost})"),
                    {cost: cardData.cost});
                this.addActionButton("button_1", buyText, callback, null, false, buyColor);
            },

            /*
             * Update all income values for the player
             */
            setIncome: function (player_id, income) {
                for (let i = 0; i < 3; i++) {
                    // Use getValue/incValue rather than setValue to get the change highlights
                    let v = this.player_income[player_id].rubles[i].getValue();
                    this.player_income[player_id].rubles[i].incValue(income.rubles[i] - v);
                    v = this.player_income[player_id].points[i].getValue();
                    this.player_income[player_id].points[i].incValue(income.points[i] - v);
                }
            },

            /*
             * Return the div id for the given card location
             */
            getCardDiv: function (row, col) {
                if (this.debug) {
                    console.log('getCardDiv', row, col, this.constants);
                }
                let div;
                switch (parseInt(row)) {
                    case this.constants.hand:
                        div = this.playerHand.getItemDivId(col);
                        break;
                    case this.constants.observatory:
                        if (col === 0) {
                            // Drawn Observatory card
                            div = this.getLocationDiv(this.constants.observatory, col, 'card');
                        } else {
                            // Observatory on table
                            div = this.player_tables[this.player_id].getItemDivId(col);
                        }
                        break;
                    case this.constants.prison:
                        // Debtor’s Prison.
                        div = this.player_tables[this.player_id].getItemDivId(col);
                        break;
                    case this.constants.discardStock:
                        // Discarded card selectable for Debtor’s Prison.
                        div = this.discardStock.getItemDivId(col);
                        break;
                    default:
                        // Board card.
                        div = this.getLocationDiv(row, col, 'card');
                        break;
                }

                return div;
            },

            /*
             * Return the div id for the given board location
             */
            getBoardDiv: function (row, col) {
                return this.getLocationDiv(row, col, 'square');
            },

            getLocationDiv: function (row, col, prefix) {
                return prefix + '_' + col + '_' + row;
            },

            /**
             * Get the card move source id for animation.
             * @param row The card row.
             * @param col The card col.
             * @param cardId The card id.
             */
            getCardSource: function (row, col, cardId) {
                if (row === this.constants.observatory) {
                    return 'stp_gameboard';
                }
                if (row === this.constants.discardRow) {
                    // Debtor’s Prison pick.
                    if (this.discardStock.count() === 0) {
                        return 'discard_pile';
                    }
                    return this.getCardDiv(this.constants.discardStock, cardId);
                }
                return this.getBoardDiv(row, col);
            },

            /*
             * Return true if button is disabled (grayed-out)
             */
            isButtonDisabled: function (button) {
                return dojo.hasClass(button.id, 'bgabutton_gray');
            },

            /*
             * Player clicks okay on note from publisher
             */
            ackPublisherMessage: function () {
                // Remove message banner
                dojo.style('publisher_msg', 'display', 'none');
                // Save user preference to not show banner
                this.bga.userPreferences.set(PREF_PUBLISHER_MESSAGE, PREF_PM_OFF);
            },

            /*
             * Build stock element for player hand and tables
             */
            createCardStock: function (elem, mode, overlap) {
                const board = new ebg.stock();
                if (overlap !== 0) {
                    board.updateDisplay = customStockUpdateDisplay;
                    board.duplicate_overlap = overlap;
                    board.observatory_type = this.constants.observatory; // needed to not overlap observatory
                    board.duplicate_vertical = this.duplicate_vertical;
                }
                board.create(this, $(elem), this.cardwidth, this.cardheight);
                board.image_items_per_row = this.card_art_row_size;
                let cards = 'cards.jpg';
                if (parseInt(this.gamedatas.version) === 2) {
                    cards = 'cards2.jpg';
                }
                if (this.debug) {
                    console.log('card_infos', this.gamedatas.card_infos);
                }
                const cardsURL = this.bga.images.getImgUrl(cards);
                for (const i in this.gamedatas.card_infos) {
                    const cardId = parseInt(i);
                    const index = this.getCardArtIndex(cardId);
                    board.addItemType(cardId, this.gamedatas.card_infos[i].weight, cardsURL, index);
                }
                board.setSelectionMode(mode);
                return board;
            },

            /*
             * Create additional content for card elements
             */
            setupNewCard: function (card_div, card_type_id, card_id) {
                this.addTooltipHtml(card_div.id, this.getCardTooltip(card_type_id, 0));

                // Observatory and debtor’s prison are only cards needing extra elements
                if ((card_type_id == this.constants.observatory || card_type_id == this.constants.prison)
                    && card_div.id.substring(0, 10) !== 'stp_myhand') {
                    // Get player and card ids to add templated html
                    const player_id = parseInt(card_div.id.split('_')[1]);
                    let id = card_id.split('_');
                    id = id[id.length - 1];
                    dojo.place(`
                        <div id="card_content_${id}">
                            <div id="card_content_mask_${id}" class="stp_maskcard"></div>
                            <div id="card_content_active_${id}" class="stp_clickcard"></div>
                        </div>`, card_div.id);

                    if (player_id === this.player_id) {
                        // Active player can click on card
                        if (card_type_id == this.constants.observatory) {
                            dojo.connect(card_div, 'onclick', this, 'onClickObservatory');
                        } else {
                            dojo.connect(card_div, 'onclick', this, 'onClickPrison');
                        }
                    } else {
                        // Other player, no active content
                        dojo.style('card_content_active_' + id, 'display', 'none');
                    }
                }
            },

            /*
             * Create additional content for card elements
             */
            setupNewDiscardedCard: function (cardDiv, cardTypeId, cardStringId) {
                if (this.debug) {
                    console.log('setupNewDiscardedCard', cardDiv, cardTypeId, cardStringId);
                }
                // Find card in possible moves:
                const splitId = cardStringId.split('_');
                const cardId = parseInt(splitId[splitId.length - 1]);
                const card = this.possible_moves[this.constants.discardStock][cardId];

                this.addTooltipHtml(cardDiv.id, this.getCardTooltip(cardTypeId, card.cost));

                cardDiv.insertAdjacentHTML('beforeend', `
                    <div id="card_content_${cardId}">
                        <div id="card_content_active_${cardId}" class="stp_clickcard"></div>
                    </div>`);
                cardDiv.addEventListener("click", this.onSelectDiscardedCard.bind(this));
            },

            getCardArtIndex: function (cardId) {
                const card = this.card_infos[cardId];
                if (typeof card.artIndex != "undefined") {
                    return parseInt(card.artIndex);
                }
                return cardId;
            },

            /*
             * Generate HTML tooltip for given card
             */
            getCardTooltip: function (card_type_id, eff_cost) {
                // Get card info and copy to modify
                const card = dojo.clone(this.card_infos[card_type_id]);
                const index = this.getCardArtIndex(card_type_id);
                // Sprite index
                const x = index % this.card_art_row_size;
                const y = Math.floor(index / this.card_art_row_size);
                card.artx = 100 * x / (this.card_art_row_size - 1);
                card.arty = 100 * y / (this.card_art_col_size - 1);

                // card type = <type> [(<worker type> | Trading card [- <worker type>])]
                if (card.card_type === "Worker") {
                    card.card_type = _(card.card_type) + " (" + _(card.card_worker_type) + ")";
                } else if (card.card_type === "Trading") {
                    card.card_type = _(card.card_trade_type) + " (" + _("Trading card");
                    if (card.card_trade_type === "Worker") {
                        card.card_type += " - " + _(card.card_worker_type);
                    }
                    card.card_type += ")";
                } else {
                    card.card_type = _(card.card_type);
                }

                // Cost and benefits
                let txt = "<p>" + _("Cost") + ": " + card.card_cost + "</p>";
                if (eff_cost > 0) {
                    txt += "<p>" + _("Effective Cost") + ": " + eff_cost + "</p>";
                }
                if (card.card_rubles > 0) {
                    txt += "<p>+" + card.card_rubles + " " + _("rubles") + "</p>";
                }
                if (card.card_points > 0) {
                    txt += "<p>+" + card.card_points + " " + _("points") + "</p>";
                }

                // Special function text
                if (typeof card.card_text != "undefined") {
                    txt += "<p>" + _(card.card_text) + "</p>";
                }

                return `
                <div class="stp_cardtooltip">
                    <h3>${_(card.card_name)}</h3>
                    <hr/>
                    <b>${card.card_type}</b>\<br/>
                    ${txt}
                    <div>
                        <div class="stp_cardart" style="background-position: ${card.artx}% ${card.arty}%;"></div>
                    </div>
                    <i>${_('Cards in play')}: ${card.card_nbr}</i>
                </div>`;
            },

            /*
             * Update backend tooltip array to move a tooltip from one div to
             * another, for when a card is moved and its id is changed
             */
            resetTooltip: function (old_id, new_id) {
                // XXX 
                // This manipulates BGA Tooltip API rather than recreating it from scratch
                // Potentially an issue with future API changes...
                if (this.tooltips[new_id]) {
                    this.tooltips[new_id].destroy();
                }
                this.tooltips[new_id] = this.tooltips[old_id];
                this.tooltips[old_id] = null;
            },

            /*
             * Set the card backs and detailed tooltip for player hand to show all cards held
             */
            updateHandTooltip: function (player_id) {
                // Update card backs in player panel
                // Remove existing icons
                for (let i = 0; i < 4; i++) {
                    const div = 'cardicon_p' + player_id + '_' + i;
                    this.removeTooltip(div);
                    dojo.removeClass(div);
                }

                // Add icons for current cards
                const backs = this.player_hand_backs[player_id];
                for (let i = 0; i < backs.length; i++) {
                    const card_type = backs[i];
                    const div = 'cardicon_p' + player_id + '_' + i;
                    dojo.addClass(div, 'stp_cardicon_' + card_type);
                    this.addTooltip(div, _(card_type) + ' ' + _('card in player hand'), '');
                }

                // Add full hand details if enabled
                if (this.player_hands[player_id]) {
                    // Sort card by type
                    let hand = this.player_hands[player_id];
                    hand = hand.sort();

                    // Clear all four template cards by default
                    const artx = [0, 0, 0, 0];
                    const arty = [0, 0, 0, 0];
                    const disp = ['none', 'none', 'none', 'none'];

                    // Display correct art for each card in hand
                    for (let i = 0; i < hand.length; i++) {
                        const index = this.getCardArtIndex(hand[i]);
                        artx[i] = 100 * (index % this.card_art_row_size) / (this.card_art_row_size - 1);
                        arty[i] = 100 * Math.floor(index / this.card_art_row_size) / (this.card_art_col_size - 1);
                        disp[i] = 'inline-block';
                    }

                    if (hand.length > 0) {
                        // Add detailed tooltip
                        const text = dojo.string.substitute(_("Cards in player hand (${nbCards}):"), {nbCards: hand.length});
                        const html = `
                            <div class="stp_cardtooltip">
                                <b>${text}</b>
                                <div>
                                    <div class="stp_cardart_small" style="background-position: ${artx[0]}% ${arty[0]}%; display: ${disp[0]}"></div>
                                    <div class="stp_cardart_small" style="background-position: ${artx[1]}% ${arty[1]}%; display: ${disp[1]}"></div>
                                    <div class="stp_cardart_small" style="background-position: ${artx[2]}% ${arty[2]}%; display: ${disp[2]}"></div>
                                    <div class="stp_cardart_small" style="background-position: ${artx[3]}% ${arty[3]}%; display: ${disp[3]}"></div>
                                </div>
                            </div>`;
                        this.addTooltipHtml('handcount_p' + player_id, html)
                        this.addTooltipHtml('handcount_icon_p' + player_id, html);
                    } else {
                        // No cards in hand - use standard tooltip
                        this.addTooltip('handcount_p' + player_id, _("Number of cards in hand"), "");
                        this.addTooltip('handcount_icon_p' + player_id, _("Number of cards in hand"), "");
                    }
                }
            },

            /*
             * Place a card on the game board at the given row,col location.
             * Card specified by its card type, source element (src)
             * for slide animation (i.e. card stack)
             */
            addCardOnBoard: function (row, col, cardType, src) {
                if (src === undefined) {
                    src = 'stp_gameboard'
                }

                this.placeNewCard(cardType, row, col);

                const discarded = (row == this.constants.discardRow);
                const cardDiv = this.getCardDiv(row, col);
                if (discarded) {
                    const discardedCard = document.getElementById(cardDiv);
                    discardedCard.classList.add("stp_discarded");
                    discardedCard.style.top = this.getCSSVariable('--stp-discard-top');
                    discardedCard.style.left = this.getCSSVariable('--stp-discard-left');
                } else {
                    this.placeOnObject(cardDiv, src);
                    this.slideCard(cardDiv, this.getBoardDiv(row, col));
                    document.getElementById(cardDiv).addEventListener("click", this.onSelectCard);
                }
                this.addTooltipHtml(cardDiv, this.getCardTooltip(cardType, 0));
            },

            updateLastDiscardedCard: function(lastDiscardedCard) {
                const previousDiscarded = document.getElementById(this.getCardDiv(this.constants.discardRow, DISCARDED_COL));
                if (null !== previousDiscarded) {
                    previousDiscarded.remove();
                }
                if (lastDiscardedCard) {
                    this.addCardOnBoard(this.constants.discardRow, DISCARDED_COL, parseInt(lastDiscardedCard.type_arg));
                }
            },

            formatCard: function (x, y, row, col) {
                return `<div class="stp_card" id="card_${col}_${row}" style="background-position:${x}% ${y}%"></div>`;
            },

            placeNewCard: function (cardType, row, col) {
                const index = this.getCardArtIndex(cardType);
                // Sprite index
                const x = 100 * (index % this.card_art_row_size) / (this.card_art_row_size - 1);
                const y = 100 * Math.floor(index / this.card_art_row_size) / (this.card_art_col_size - 1);

                if (this.debug) {
                    console.log(`adding card type ${cardType} with index ${index} -> x ${x} y ${y} at col ${col} and row ${row}`);
                }
                dojo.place(this.formatCard(x, y, row, col), 'stp_cards');
            },

            slideCard: function (cardDiv, dest) {
                const animation = this.slideToObject(cardDiv, dest);
                dojo.connect(animation, 'onEnd', () => {
                    this.placeAt(cardDiv, dest);
                });
                animation.play();
            },

            placeAt: function (cardDivId, destId) {
                const destElem = document.getElementById(destId);
                const cardElem = document.getElementById(cardDivId);
                cardElem.style.top = destElem.style.top;
                cardElem.style.left = destElem.style.left;
            },

            discardPlayerCard: function(playerId, cardId) {
                const discardedCard = this.player_tables[playerId].getItemById(cardId);
                if (this.debug) {
                    console.log('discardPlayerCard', playerId, cardId, discardedCard);
                }
                // Duplicate the card going to be removed from player table to place it on top of to be removed one.
                this.placeNewCard(discardedCard.type, this.constants.discardRow, MOVE_DISCARD_COL);
                const discardedCardDivId = this.getCardDiv(this.constants.discardRow, MOVE_DISCARD_COL);
                this.placeOnObject(discardedCardDivId, `stp_playertable_${playerId}_item_${discardedCard.id}`);
                // Remove from player table.
                this.player_tables[playerId].removeFromStockById(discardedCard.id);
                // Slide copy to discard.
                const anim = this.slideToObject(discardedCardDivId, 'discard_pile');
                dojo.connect(anim, 'onEnd', ()=> {
                    this.setAsLastDiscarded(discardedCardDivId);
                });
                anim.play();
            },

            /*
             * Rotate and/or place starting player tokens on player boards
             */
            setTokens: function (tokens, animate) {
                const delay = 1000; // 1s animation
                const players = {};

                // Clear existing tokens
                dojo.query('.stp_token').removeClass('stp_token_Worker stp_token_Building stp_token_Aristocrat stp_token_Trading');

                // Determine current and next player for each token
                for (const phase in tokens) {
                    const token = tokens[phase];
                    let curr;
                    let next;
                    if (players[token.next]) {
                        // Next player already has one token, use second slot
                        curr = 'token2_p' + token.current;
                        next = 'token2_p' + token.next;
                    } else {
                        curr = 'token_p' + token.current;
                        next = 'token_p' + token.next;
                        players[token.next] = true;
                    }

                    if (animate) {
                        // Use temp object to show tokens rotating between boards
                        const tmp = '<div id="tmp_token_' + phase + '" class="stp_token stp_token_' + phase + '"></div>';
                        this.slideTemporaryObject(tmp, 'token_wrap_p' + token.current, curr, next, delay, 0);
                    } else {
                        // Immediately switch token to next player
                        dojo.addClass(next, 'stp_token_' + phase);
                        const txt = dojo.string.substitute(_("Starting player for ${phase} phase"), {phase: _(phase)});
                        this.addTooltip(next, txt, "");
                    }
                }

                if (animate) {
                    // Call this function again without animation to set permanent token icons
                    // A bit overly complicated but animation callbacks were not working
                    setTimeout(dojo.hitch(this, function () {
                        this.setTokens(tokens, false);
                    }), delay);
                }
            },

            /*
             * Rotate card stacks for given phase
             */
            setPhase: function (phase) {
                const prev_phase = this.current_phase;
                this.current_phase = phase;
                if (prev_phase !== '') {
                    // Reset tooltip for previous phase deck
                    this.setDeckTooltip(prev_phase, this.deck_counters[prev_phase].getValue());
                }

                // Update label with current phase and matching color
                $('stp_phase_label').textContent = _('Current phase') + ': ' + _(phase);
                if (phase === 'Worker') {
                    dojo.style('stp_phase_label', 'color', 'green');
                } else if (phase === 'Building') {
                    dojo.style('stp_phase_label', 'color', 'blue');
                } else if (phase === 'Aristocrat') {
                    dojo.style('stp_phase_label', 'color', 'orangered');
                } else if (phase === 'Trading') {
                    dojo.style('stp_phase_label', 'color', 'black');
                }

                // Get platform-specific animation
                let transform;
                dojo.forEach(
                    ['transform', 'WebkitTransform', 'msTransform',
                        'MozTransform', 'OTransform'],
                    function (name) {
                        if (typeof dojo.body().style[name] != 'undefined') {
                            transform = name;
                        }
                    }
                );

                // Card stack for current phase is up and all others rotated 90 deg
                // Rotate all stacks to the correct angle for the given phase
                dojo.forEach(this.phases, function (name) {
                    const node = dojo.byId('deck_' + name);
                    let curve = [-90, -90]; // no-op

                    if (name === phase) {
                        // Current phase -> up, clockwise
                        curve = [-90, 0];
                    } else if (node.style[transform] !== 'rotate(-90deg)') {
                        // Initial state or previous phase -> 90 anti-clockwise
                        curve = [0, -90];
                    }

                    // Rotate
                    new dojo.Animation({
                        curve: curve,
                        onAnimate: function (v) {
                            node.style[transform] = 'rotate(' + v + 'deg)';
                        }
                    }).play();
                });
            },

            /*
             * Highlight selected card and any possible moves
             */
            setSelections: function () {
                if (this.debug) {
                    console.log('setSelections', this.possible_moves, this.is_trading);
                }

                if (this.is_trading) {
                    // Player is acting on a trading card
                    // Highlight possible trades on table
                    const row = this.client_state_args.row;
                    const col = this.client_state_args.col;
                    const card_info = this.possible_moves[row][col];

                    for (const i in card_info.trades) {
                        const div = this.player_tables[this.player_id].getItemDivId(card_info.trades[i]);
                        dojo.addClass(div, 'stp_selectable');
                    }

                    // Let player select a card on their table
                    this.playerTable.setSelectionMode(1);
                } else {
                    // Player can select a card to add/buy/play
                    // Highlight all possible moves
                    for (const row in this.possible_moves) {
                        for (const col in this.possible_moves[row]) {
                            const card = this.possible_moves[row][col];
                            if (card.can_buy || card.can_add) {
                                const div = this.getCardDiv(row, col);
                                if (this.debug) {
                                    console.log('setSelections', row, col, card, div);
                                }
                                dojo.addClass(div, 'stp_selectable');

                                // Update card tooltip with adjusted cost
                                if (row != this.constants.observatory && row != this.constants.prison) {
                                    this.addTooltipHtml(div, this.getCardTooltip(card.card_type, card.cost));
                                }
                            }
                        }
                    }
                }

                // Highlight currently selected card, if any
                const row = this.client_state_args.row;
                const col = this.client_state_args.col;
                if (row !== undefined && col !== undefined) {
                    const div = this.getCardDiv(row, col);
                    if (this.debug) {
                        console.log('SELECTED: ', row, col, div);
                    }
                    dojo.removeClass(div, 'stp_selectable');
                    dojo.addClass(div, 'stp_selected');
                }
            },

            /*
             * Add card drawn with Observatory to middle of board
             */
            showObservatoryChoice: function (obs_id, card, effectiveCost) {
                if (this.debug) {
                    console.log('showObservatoryChoice', obs_id, card, effectiveCost);
                }
                const card_id = this.getCardDiv(this.constants.observatory, 0);
                if ($(card_id)) {
                    // Card already exists on board
                    // Player must have canceled last action
                    dojo.addClass(card_id, 'stp_selected');
                    return;
                }

                // Disable Observatory
                dojo.style('card_content_mask_' + obs_id, 'display', 'block');

                // Remove one card from selected deck
                const num_cards = this.deck_counters[card.type].incValue(-1);
                this.setDeckTooltip(card.type, num_cards);

                const cardType = parseInt(card.type_arg);
                // Place and animate card draw
                this.placeNewCard(cardType, this.constants.observatory, 0);
                this.placeOnObject(card_id, 'deck_' + card.type);
                dojo.addClass(card_id, 'stp_selected');
                this.slideToObject(card_id, 'stp_gameboard').play();
                this.addTooltipHtml(card_id, this.getCardTooltip(cardType, effectiveCost));
            },

            showDiscardStock: function (possibleMoves) {
                if (this.debug) {
                    console.log('showDiscardStock', possibleMoves);
                }

                // Disable Debtor’s Prison:
                document.getElementById('card_content_mask_' + this.gamedatas.prison.id).style.display = 'block';
                // Show discard stock:
                document.getElementById("stp_discard_stock_container").style.display = 'block';
                // Add cards only if not already done.
                if (this.discardStock.count() === 0) {
                    for (const index in possibleMoves) {
                        const card = possibleMoves[index];
                        this.discardStock.addToStockWithId(
                            card.card_type, card.card_id, 'discard_pile');
                    }
                }
                // Hide last discarded card as it is going to be displayed in stock now:
                document.getElementById(this.getCardDiv(this.constants.discardRow, DISCARDED_COL)).style.display = 'none';
            },

            hideDiscardStock: function() {
                if (this.discardStock !== null) {
                    const discardedCards = this.discardStock.getAllItems();
                    if (discardedCards.length === 0) {
                        // No animation of cards going back to discard, hide discard stock immediately.
                        document.getElementById("stp_discard_stock_container").style.display = 'none';
                    }
                    for (const index in discardedCards) {
                        if (this.debug) {
                            console.log('hideDiscardStock', discardedCards[index]);
                        }
                        // Move back card to discard pile.
                        const discardedCard = this.discardStock.getItemById(discardedCards[index].id);
                        const col = MOVE_DISCARD_COL + parseInt(index);
                        // Duplicate the card going to be removed from discard stock to place it on top of to be removed one.
                        this.placeNewCard(discardedCard.type, this.constants.discardRow, col);
                        const discardedCardDivId = this.getCardDiv(this.constants.discardRow, col);
                        this.placeOnObject(discardedCardDivId, `stp_discard_stock_item_${discardedCard.id}`);
                        // Slide copy to discard.
                        const anim = this.slideToObject(discardedCardDivId, 'discard_pile');
                        dojo.connect(anim, 'onEnd', ()=> {
                            document.getElementById(discardedCardDivId).remove();
                            document.getElementById("stp_discard_stock_container").style.display = 'none';
                        });
                        anim.play();
                    }
                    this.discardStock.removeAll();
                }
            },

            /*
             * Generate tooltip for given card stack showing number of cards left
             * [Current phase: ] <phase> stack has <cards> cards
             * OR
             *   "       "         "       "   is empty meaning game will end soon
             */
            setDeckTooltip: function (phase, cards) {
                let txt = "";
                if (phase === this.current_phase) {
                    // Mark as active phase
                    txt += "<b>" + _("Current phase") + ":</b> ";
                }


                if (cards === 0) {
                    // Special message if stack is empty (end game trigger)
                    txt += dojo.string.substitute(_("${phase} stack is empty! Game will end when this round completes"), {
                        phase: _(phase)
                    });
                } else {
                    txt += dojo.string.substitute(_("${phase} stack has ${cards} cards"), {
                        phase: _(phase),
                        cards: cards
                    });
                }

                this.addTooltip('deck_' + phase, txt, "");
            },

            ///////////////////////////////////////////////////
            //// Player's action
            //

            /*
             * Player clicks an active card
             */
            onSelectCard: function (evt) {
                dojo.stopEvent(evt);
                if (this.debug) {
                    console.log('onSelectCard', evt);
                }
                // Selection of a card is allowed if and only if add card action is allowed (not taking hand in account).
                if (!this.checkAction('actAddCard'))
                    return;

                // Clear any previous selection
                this.client_state_args = {};

                // Card location
                const coords = evt.currentTarget.id.split('_');
                const col = parseInt(coords[1]);
                const row = parseInt(coords[2]);
                const card_info = this.possible_moves[row][col];

                this.client_state_args.col = col;
                this.client_state_args.row = row;

                let desc;
                if (this.gamedatas.buyOnly) {
                    desc = _("${card_name}: ${you} may buy");
                } else {
                    desc = _("${card_name}: ${you} may buy or add to hand");
                }
                this.setClientState('client_selectCard', {
                    descriptionmyturn: desc,
                    args: card_info
                });
            },

            /*
             * Player clicks a discarded card (Debtor’s Prison action).
             */
            onSelectDiscardedCard: function (evt) {
                dojo.stopEvent(evt);
                if (this.debug) {
                    console.log('onSelectDiscardedCard', evt.currentTarget);
                }

                // Clear any previous selection
                this.client_state_args = {};

                // Card location
                const splitId = evt.currentTarget.id.split('_');
                const col = parseInt(splitId[splitId.length - 1]);
                const row = this.constants.discardStock;
                const card_info = this.possible_moves[row][col];

                this.client_state_args.col = col;
                this.client_state_args.row = row;
                this.client_state_args.cardId = col;

                this.setClientState('client_selectCard', {
                    descriptionmyturn: "${card_name}: ${you} may buy or add to hand or discard",
                    args: card_info
                });
            },

            /*
             * Player clicks 'Add to hand' button
             */
            onAddCard: function (evt) {
                dojo.stopEvent(evt);
                if (!this.checkAction('actAddCard'))
                    return;

                if (this.isButtonDisabled(evt.target)) {
                    this.showMessage(_("Your hand is full"), "error");
                    return;
                }

                this.bga.actions.performAction('actAddCard', this.client_state_args);
            },

            /*
             * Player clicks 'Buy' button for card
             */
            onBuyCard: function (evt) {
                dojo.stopEvent(evt);
                if (!this.checkAction('actBuyCard'))
                    return;

                // Get card info to handle trading cards
                const col = this.client_state_args.col;
                const row = this.client_state_args.row;
                const card_info = this.possible_moves[row][col];

                if (this.isButtonDisabled(evt.target)) {
                    // Player cannot buy
                    // Check if trading card to give most accurate error message
                    if (card_info.is_trading && !card_info.has_trade) {
                        this.showMessage(_("You do not have any valid cards to trade"), "error");
                    } else {
                        this.showMessage(_("You do not have enough rubles"), "error");
                    }
                    return;
                }

                if (card_info.is_trading) {
                    // Player needs to select card to displace
                    const desc = _(card_info.card_name) + ': ' + _('${you} must choose a card to displace (base cost: ${cost})');
                    this.setClientState('client_tradeCard', {
                        descriptionmyturn: desc,
                        args: card_info
                    });
                } else {
                    // Send buy action to server
                    this.bga.actions.performAction('actBuyCard', this.client_state_args);
                }
            },

            /*
             * Player clicks 'Buy' button for card (from hand)
             */
            onPlayCard: function (evt) {
                dojo.stopEvent(evt);
                if (!this.checkAction('actPlayCard')) {
                    this.playerHand.unselectAll();
                    return;
                }

                // Get card to be played
                const col = this.client_state_args.col;
                const row = this.client_state_args.row;
                const card = this.possible_moves[row][col];

                if (!card.can_buy) {
                    // Player cannot play this card
                    // Check if trading card to give most accurate error message
                    if (card.is_trading && !card.has_trade) {
                        this.showMessage(_("You do not have any valid cards to trade"), "error");
                    } else {
                        this.showMessage(_("You do not have enough rubles"), "error");
                    }
                    this.playerHand.unselectAll();
                    return;
                }

                if (card.is_trading) {
                    // Player needs to select card to displace
                    const desc = _(card.card_name) + ': ' + _('${you} must choose a card to displace (base cost: ${cost})');
                    this.setClientState('client_tradeCard', {
                        descriptionmyturn: desc,
                        args: card
                    });
                } else {
                    // Send play action to server
                    this.bga.actions.performAction('actPlayCard', {card_id: this.client_state_args.col});
                }

                this.playerHand.unselectAll();
            },

            /*
             * Player clicks 'Cancel' button (several actions).
             */
            onCancelCard: function (evt) {
                dojo.stopEvent(evt);
                // Cancel is allowed if and only if add card action is allowed (not taking rubles and hand in account).
                if (this.checkAction('actAddCard')) {
                    // Reset to main state
                    this.restoreServerGameState();
                }
            },

            /*
             * Player clicks 'Pass' button (not for Pub)
             */
            onPass: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actPass');
            },

            /*
             * Player clicks 'Auto pass' button
             */
            onAutoPass: function (evt) {
                dojo.stopEvent(evt);
                const pass = this.isAutoPassImmediate() && !this.gamedatas.autopass && this.isCurrentPlayerActive()
                    && !this.gamedatas.buyOnly && this.checkAction('actPass', true);
                if (this.debug) {
                    console.log('onAutoPass', pass);
                }
                // No action check (player can be not active)
                this.bga.actions.performAction('actAutoPass', {
                    pass: pass
                }, {checkAction: false});
            },

            /*
             * Player clicks 'Cancel' button on auto pass banner
             */
            onCancelAutoPass: function (evt) {
                dojo.stopEvent(evt);
                // No action check (player may not be active)
                this.bga.actions.performAction('actCancelAutoPass', {}, {checkAction: false});
            },

            /*
             * Player clicks '-1' button for Pub
             */
            onOneLessPoint: function (evt) {
                dojo.stopEvent(evt);

                this.pub_points -= 1;

                if (this.pub_points < 0) {
                    // Cannot go below zero
                    this.showMessage(_("You cannot buy fewer than zero"), "error");
                    this.pub_points = 0;
                }

                if (this.pub_points === 0) {
                    // "Disable" -1 button
                    dojo.removeClass('button_1', 'bgabutton_blue');
                    dojo.addClass('button_1', 'bgabutton_gray');
                }

                if (this.pub_points < this.max_pub_points) {
                    // "Enable" +1 button
                    dojo.removeClass('button_2', 'bgabutton_gray');
                    dojo.addClass('button_2', 'bgabutton_blue');
                }

                // Update button text with current points and cost info
                $('button_3').textContent = _("Buy") + " " + this.pub_points + " (" + this.pub_points * 2 + ")";
            },

            /*
             * Player clicks '+1' button for Pub
             */
            onOneMorePoint: function (evt) {
                dojo.stopEvent(evt);

                this.pub_points += 1;

                if (this.pub_points > this.max_pub_points) {
                    // Cannot go above max (provided by server)
                    this.showMessage(_("You cannot buy any more points"), "error");
                    this.pub_points = this.max_pub_points;
                }

                if (this.pub_points === this.max_pub_points) {
                    // "Disable" +1 button
                    dojo.removeClass('button_2', 'bgabutton_blue');
                    dojo.addClass('button_2', 'bgabutton_gray');
                }

                if (this.pub_points > 0) {
                    // "Enable" -1 button
                    dojo.removeClass('button_1', 'bgabutton_gray');
                    dojo.addClass('button_1', 'bgabutton_blue');
                }

                // Update button text with current points and cost info
                $('button_3').textContent = _("Buy") + " " + this.pub_points + " (" + this.pub_points * 2 + ")";
            },

            /*
             * Player clicks 'Buy' button for Pub
             */
            onBuyPoints: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actBuyPoints', {points: this.pub_points});
                this.pub_points = 0;
            },

            /*
             * Player clicks 'Buy' button for Trading House.
             */
            onTradingHouseBuyPoints: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actBuyPoints');
            },

            /*
             * Player clicks 'Pass' button for Trading House.
             */
            onTradingHousePass: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actPass');
            },

            /*
             * Player clicks '4 rubles' button for Guild Hall.
             */
            onGuildHall4R: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actChoose', { rubles: 4});
            },

            /*
             * Player clicks '3 rubles 1 point' button for Guild Hall.
             */
            onGuildHall3R1P: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actChoose', { rubles: 3});
            },

            /*
             * Player clicks '2 rubles 2 points' button for Guild Hall.
             */
            onGuildHall2R2P: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actChoose', { rubles: 2});
            },

            /*
             * Player clicks '1 ruble 3 points' button for Guild Hall.
             */
            onGuildHall1R3P: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actChoose', { rubles: 1});
            },

            /*
             * Player clicks '4 points' button for Guild Hall.
             */
            onGuildHall4P: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actChoose', { rubles: 0});
            },

            /*
             * Player clicks a card in their hand
             */
            onPlayerHandSelectionChanged: function () {
                const items = this.playerHand.getSelectedItems();

                if (items.length > 0) {
                    if (this.checkAction('actPlayCard')) {
                        // Clear any previous selection
                        this.client_state_args = {};

                        // Store card details
                        const card_id = items[0].id;
                        this.client_state_args.col = card_id;
                        this.client_state_args.row = this.constants.hand;
                        const card = this.possible_moves[this.constants.hand][card_id];

                        // Allow player to see cost and confirm buy
                        const desc = _(card.card_name) + ': ' + _('${you} may buy');
                        this.setClientState('client_playCard', {
                            descriptionmyturn: desc,
                            args: card
                        });
                    } else {
                        // Cannot play from hand right now
                        this.playerHand.unselectAll();
                    }
                }
            },

            /*
             * Player clicks a card on their table
             */
            onPlayerTableSelectionChanged: function () {
                const items = this.playerTable.getSelectedItems();
                if (this.debug) {
                    console.log('onPlayerTableSelectionChanged', items, this.is_trading);
                }

                if (items.length > 0) {
                    if (this.checkAction('actBuyCard') && this.is_trading) {
                        // Displace card with trading card
                        this.client_state_args.trade_id = parseInt(items[0].id);
                        const card_info = this.possible_moves[this.client_state_args.row][this.client_state_args.col];
                        if (this.debug) {
                            console.log('onPlayerTableSelectionChanged', card_info, this.client_state_args);
                        }

                        if (!card_info.trades.includes(this.client_state_args.trade_id)) {
                            const selectedCardInfo = this.card_infos[items[0].type];
                            const tradeCardInfo = this.card_infos[card_info.card_type];
                            if (this.debug) {
                                console.log('onPlayerTableSelectionChanged', tradeCardInfo, selectedCardInfo);
                            }
                            const tradeCost = Math.max(1, parseInt(card_info.cost) - selectedCardInfo.card_cost);
                            if (tradeCost > this.player_rubles[this.bga.players.getCurrentPlayerId()].getValue()) {
                                this.showMessage(_("You do not have enough rubles"), "error");
                            } else {
                                // Player as enough money. If
                                if (parseInt(items[0].type) === parseInt(this.constants.observatory) && tradeCardInfo.card_trade_type === "Building") {
                                    // Card to displace is observatory and trade card trade type is building, it means
                                    // that the observatory was used and can not be displaced.
                                    this.showMessage(_("You cannot displace an Observatory after using it"), "error");
                                } else {
                                    // Only remaining case is the selected card is not of the right type.
                                    this.showMessage(_("Wrong type of card to displace"), "error");
                                }
                            }
                        } else {
                            if (parseInt(this.client_state_args.row) === parseInt(this.constants.hand)) {
                                // Play from hand
                                this.bga.actions.performAction('actPlayCard',
                                    {card_id: this.client_state_args.col, trade_id: this.client_state_args.trade_id});
                            } else {
                                // Buy from board
                                this.bga.actions.performAction('actBuyCard', this.client_state_args);
                            }
                            this.playerTable.unselectAll();
                        }
                    } else {
                        // Cannot trade cards right now
                        this.showMessage(_("You must select first select a card to buy"), "error");
                        this.playerTable.unselectAll();
                    }
                }
            },

            /*
             * Player clicks Observatory on their board
             */
            onClickObservatory: function (evt) {
                dojo.stopEvent(evt);

                if (this.is_trading) {
                    // In trade state
                    // Do not register click and let state machine handle the rest
                    return;
                }

                const obs_card_id = evt.currentTarget.id.split('_')[3];
                if (this.client_state_args.card_id == obs_card_id) {
                    // Already in client state for Observatory
                    // Player needs to click choose a deck or cancel
                    this.showMessage(_("You must select a card stack on the board"), "error");
                    return;
                }

                if (dojo.getStyle('card_content_mask_' + obs_card_id, 'display') != 'none') {
                    // Observatory card already used (mask is on)
                    this.showMessage(_("You can only use an Observatory once per round"), "error");
                    return;
                }

                this.useObservatory(obs_card_id);
            },

            /*
             * Player clicks Debtor’s Prison on their board
             */
            onClickPrison: function (evt) {
                dojo.stopEvent(evt);

                if (this.is_trading) {
                    // In trade state
                    // Do not register click and let state machine handle the rest
                    return;
                }

                if (this.current_phase !== this.phases[1]) {
                    // Not building phase, can't use
                    this.showMessage(_("You can only use the Debtor’s Prison during the Building phase"), "error");
                    return;
                }

                if (dojo.getStyle('card_content_mask_' + this.gamedatas.prison.id, 'display') !== 'none') {
                    // Prison card already used (mask is on)
                    this.showMessage(_("You can only use the Debtor’s Prison once per round"), "error");
                    return;
                }

                this.bga.actions.performAction('actUsePrison');
            },

            /*
             * Player clicks Observatory button
             */
            onButtonObservatory: function (evt) {
                dojo.stopEvent(evt);

                // No card event to pull id from so just use first listed card in moves
                for (const i in this.possible_moves[this.constants.observatory]) {
                    this.useObservatory(i);
                    return;
                }
            },

            /*
             * Player clicks prison button
             */
            onButtonPrison: function (evt) {
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actUsePrison');
            },

            /*
             * Player uses Observatory (card or button)
             */
            useObservatory: function (obs_card_id) {
                if (!this.checkAction('actUseObservatory'))
                    return;

                if (this.current_phase !== this.phases[1]) {
                    // Not building phase, can't use
                    this.showMessage(_("You can only use the Observatory during the Building phase"), "error");
                    return;
                }

                this.client_state_args.card_id = obs_card_id;

                this.setClientState('client_useObservatory', {
                    descriptionmyturn: _('Observatory: ${you} must choose a stack to draw from')
                });
            },

            /*
             * Player clicks a card stack
             */
            onClickDeck: function (evt) {
                if (this.debug) {
                    console.log('onClickDeck: ' + evt.currentTarget.id);
                }
                dojo.stopEvent(evt);
                if (this.checkAction('actUseObservatory')) {
                    if (dojo.hasClass(evt.currentTarget.id, 'stp_selectable')) {
                        this.bga.actions.performAction('actUseObservatory', {
                            deck: evt.currentTarget.id,
                            card_id: this.client_state_args.card_id
                        });
                    } else {
                        const phase = evt.currentTarget.id.split('_')[1];
                        const cardsLeft = this.deck_counters[phase].getValue()
                        if (cardsLeft === 0) {
                            this.showMessage(_("Card stack is empty"), "error");
                        } else {
                            this.showMessage(_("You cannot draw the last card"), "error");
                        }
                    }
                }
            },

            /*
             * Player clicks 'Discard' button for Observatory or Debtor’s Prison.
             */
            onDiscardCard: function (evt) {
                if (this.debug) {
                    console.log('onDiscardCard');
                }
                dojo.stopEvent(evt);
                this.bga.actions.performAction('actDiscardCard', this.client_state_args);
            },


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your saintpetersburg.game.php file.
            
            */
            setupNotifications: function () {
                if (this.debug) {
                    console.log('notifications subscriptions setup');
                }

                dojo.subscribe('autopass', this, 'notif_autoPass');
                dojo.subscribe('pass', this, 'notif_pass');
                dojo.subscribe('buyCard', this, 'notif_buyCard');
                dojo.subscribe('addCard', this, 'notif_addCard');
                dojo.subscribe('playCard', this, 'notif_playCard');
                dojo.subscribe('shiftRight', this, 'notif_shiftRight');
                this.notifqueue.setSynchronous('shiftRight', 1000);
                dojo.subscribe('shiftDown', this, 'notif_shiftDown');
                this.notifqueue.setSynchronous('shiftDown', 500);
                dojo.subscribe('discard', this, 'notif_discard');
                this.notifqueue.setSynchronous('discard', 1000);
                dojo.subscribe('tableDiscard', this, 'notifTableDiscard');
                this.notifqueue.setSynchronous('tableDiscard', 1000);
                dojo.subscribe('scorePhase', this, 'notif_scorePhase');
                dojo.subscribe('nextPhase', this, 'notif_nextPhase');
                this.notifqueue.setSynchronous('nextPhase', 1000);
                dojo.subscribe('newScores', this, 'notif_newScores');
                dojo.subscribe('lastRound', this, 'notif_lastRound');
                dojo.subscribe('newRound', this, 'notif_newRound');
                dojo.subscribe('buyPoints', this, 'notif_buyPoints');
                dojo.subscribe('observatory', this, 'notif_observatory');
                dojo.subscribe('guildHall', this , 'notifGuildHall');
            },

            /*
             * Message for player toggling auto pass
             */
            notif_autoPass: function (notif) {
                if (this.debug) {
                    console.log('autopass notif', notif);
                }

                if (notif.args.enable) {
                    // Turn on warning banner
                    dojo.style('autopass_msg', 'display', '');
                    this.gamedatas.autopass = true;

                    // Remove Auto pass action button
                    dojo.destroy("button_autopass");
                } else {
                    // Turn off warning banner
                    dojo.style('autopass_msg', 'display', 'none');
                    this.gamedatas.autopass = false;

                    // Restore Auto pass action button
                    this.addActionButton("button_autopass", _("Enable auto pass"), "onAutoPass", null, false, "red");
                }
            },

            /*
             * Message for player passing
             */
            notif_pass: function (notif) {
                if (this.debug) {
                    console.log('pass notif', notif);
                }

                // Shade player panel to indicate pass
                this.disablePlayerPanel(notif.args.player_id);
            },


            /*
             * Message for player buying card
             */
            notif_buyCard: function (notif) {
                if (this.debug) {
                    console.log('notif_buyCard', notif);
                }

                // Clear all pass
                this.enableAllPlayerPanels();

                // Card position on board
                const row = notif.args.card_row;
                let col = notif.args.card_loc;
                const src = this.getCardSource(row, col, notif.args.card_id);

                if (row === this.constants.observatory) {
                    col = 0;
                } else if (row === this.constants.discardRow) {
                    // Update last discarded card as it may be the one taken.
                    this.updateLastDiscardedCard(notif.args.lastDiscarded);
                }

                if (notif.args.trade_id > 0) {
                    // Remove displaced card from table.
                    this.discardPlayerCard(notif.args.player_id, notif.args.trade_id);
                }

                // Move card from board to player table
                if (row !== this.constants.discardRow) {
                    dojo.destroy('card_' + col + '_' + row);
                }
                this.player_tables[notif.args.player_id].addToStockWithId(
                    notif.args.card_idx, notif.args.card_id, src);
                if (row === this.constants.discardRow && this.discardStock.count() > 0) {
                    this.discardStock.removeFromStockById(notif.args.card_id);
                    this.hideDiscardStock();
                }

                if (this.player_rubles[notif.args.player_id]) {
                    // Active player sees ruble count after playing cost
                    // (either their own or with game option enabled for others)
                    this.player_rubles[notif.args.player_id].incValue(-notif.args.card_cost);
                }

                this.player_aristocrats[notif.args.player_id].setValue(notif.args.aristocrats);
                this.setIncome(notif.args.player_id, notif.args.income);
                // Board size might have to be adjusted to keep all current player bought cards visible.
                this.adaptInterface();
            },

            /*
             * Message for player adding card to their hand
             */
            notif_addCard: function (notif) {
                if (this.debug) {
                    console.log('notif_addCard', notif);
                }

                // Clear all pass
                this.enableAllPlayerPanels();

                // Card position on board
                const row = notif.args.card_row;
                let col = notif.args.card_loc;
                const src = this.getCardSource(row, col, notif.args.card_id);

                if (row === this.constants.observatory) {
                    col = 0;
                } else if (row === this.constants.discardRow) {
                    // Update last discarded card as it may be the one taken.
                    this.updateLastDiscardedCard(notif.args.lastDiscarded);
                }

                if (this.player_id == notif.args.player_id) {
                    // Active player - add card to hand
                    if (row !== this.constants.discardRow) {
                        document.getElementById(this.getCardDiv(row, col)).remove();
                    }
                    this.playerHand.addToStockWithId(notif.args.card_idx, notif.args.card_id, src);
                    if (row === this.constants.discardRow && this.discardStock.count() > 0) {
                        this.discardStock.removeFromStockById(notif.args.card_id);
                        this.hideDiscardStock();
                    }
                } else {
                    let cardDivId;
                    if (row !== this.constants.discardRow) {
                        cardDivId = this.getCardDiv(row, col);
                    } else {
                        // Create the card and place it on discard pile.
                        this.placeNewCard(notif.args.card_idx, this.constants.discardRow, MOVE_DISCARD_COL);
                        cardDivId = this.getCardDiv(this.constants.discardRow, MOVE_DISCARD_COL);
                        this.placeOnObject(cardDivId, src);
                    }
                    // Other player - move card to player board and destroy
                    const anim = this.slideToObject(cardDivId,
                        this.bga.playerPanels.getElement(notif.args.player_id));
                    dojo.connect(anim, 'onEnd', function (node) {
                        dojo.destroy(node);
                    });
                    anim.play();
                }

                // Update hand count on player board
                this.player_hand_counts[notif.args.player_id].incValue(1);

                // Update hand tooltip and card backs
                if (this.player_hands[notif.args.player_id]) {
                    this.player_hands[notif.args.player_id].push(notif.args.card_idx);
                }
                const card_type = this.card_infos[notif.args.card_idx]['card_type'];
                this.player_hand_backs[notif.args.player_id].push(card_type);
                this.updateHandTooltip(notif.args.player_id);
            },

            /*
             * Message for player playing a card from their hand.
             */
            notif_playCard: function (notif) {
                if (this.debug) {
                    console.log('notif_playCard', notif);
                }

                // Clear all pass
                this.enableAllPlayerPanels();

                if (notif.args.trade_id > 0) {
                    // Remove displaced card from table
                    this.discardPlayerCard(notif.args.player_id, notif.args.trade_id);
                }

                if (notif.args.player_id == this.player_id) {
                    // Active player - move card from hand to table
                    this.playerTable.addToStockWithId(
                        notif.args.card_idx, notif.args.card_id,
                        'stp_myhand_item_' + notif.args.card_id);
                    this.playerHand.removeFromStockById(notif.args.card_id);
                } else {
                    // Other players - add card to table
                    this.player_tables[notif.args.player_id].addToStockWithId(
                        notif.args.card_idx, notif.args.card_id,
                        // Despite linter warning, it is working that way.
                        this.bga.playerPanels.getElement(notif.args.player_id));
                }

                if (this.player_rubles[notif.args.player_id]) {
                    // Active player sees ruble count after playing cost
                    // (either their own or with game option enabled for others)
                    this.player_rubles[notif.args.player_id].incValue(-notif.args.card_cost);
                }

                // Update hand count on player board
                this.player_hand_counts[notif.args.player_id].incValue(-1);

                this.player_aristocrats[notif.args.player_id].setValue(notif.args.aristocrats);
                this.setIncome(notif.args.player_id, notif.args.income);

                // Update hand tooltip and card backs
                if (this.player_hands[notif.args.player_id]) {
                    const idx = this.player_hands[notif.args.player_id].indexOf(notif.args.card_idx);
                    this.player_hands[notif.args.player_id].splice(idx, 1);
                }
                const card_type = this.card_infos[notif.args.card_idx]['card_type'];
                const idx = this.player_hand_backs[notif.args.player_id].indexOf(card_type);
                this.player_hand_backs[notif.args.player_id].splice(idx, 1);
                this.updateHandTooltip(notif.args.player_id);
            },

            /*
             * Move all cards on board to right-most positions
             */
            notif_shiftRight: function (notif) {
                if (this.debug) {
                    console.log('notif shift right', notif);
                }

                const row = notif.args.row;
                for (const i in notif.args.columns) {
                    const old_col = i;
                    const new_col = notif.args.columns[i];
                    if (new_col !== old_col) {
                        const old_card = this.getCardDiv(row, old_col);
                        const new_card = this.getCardDiv(row, new_col);
                        // Update card DOM id for new position
                        dojo.attr(old_card, 'id', new_card);
                        // Slide card right to new position
                        this.slideCard(new_card, this.getBoardDiv(row, new_col));
                        this.resetTooltip(old_card, new_card);
                    }
                }

            },

            /*
             * Move all cards on board from upper to lower row
             */
            notif_shiftDown: function (notif) {
                if (this.debug) {
                    console.log('notif shift down', notif);
                }

                for (const i in notif.args.columns) {
                    const col = notif.args.columns[i];
                    const old_card = this.getCardDiv(0, col);
                    const new_card = this.getCardDiv(1, col);
                    // Update card DOM id for new position
                    dojo.attr(old_card, 'id', new_card);
                    this.slideCard(new_card, this.getBoardDiv(1, col));
                    this.resetTooltip(old_card, new_card);
                }
            },

            /*
             * Message for each player's end of phase scoring
             */
            notif_scorePhase: function (notif) {
                if (this.debug) {
                    console.log('notif score phase', notif);
                }

                if (this.player_rubles[notif.args.player_id]) {
                    // Active player sees ruble count after playing cost
                    // (either their own or with game option enabled for others)
                    this.player_rubles[notif.args.player_id].incValue(notif.args.rubles);
                }
            },

            /*
             * Message for new phase starting
             */
            notif_nextPhase: function (notif) {
                if (this.debug) {
                    console.log('notif next phase', notif);
                }
                this.gamedatas.buyOnly = false;

                // Clear pass and remove auto pass banners
                this.enableAllPlayerPanels();
                dojo.style('autopass_msg', 'display', 'none');
                this.gamedatas.autopass = false;

                // Rotate card stacks
                this.setPhase(notif.args.phase_arg);
                const deck = 'deck_' + notif.args.phase_arg;

                // Draw new cards onto board
                let draw = 0;
                for (const i in notif.args.cards) {
                    this.addCardOnBoard(0, i, parseInt(notif.args.cards[i]), deck);
                    draw++;
                }

                // Update deck counters and tooltips
                const num_cards = this.deck_counters[notif.args.phase_arg].incValue(-draw);
                this.setDeckTooltip(notif.args.phase_arg, num_cards);
                if (num_cards === 0) {
                    // Highlight that stack is empty and game is in end state
                    dojo.addClass(deck, 'stp_emptydeck')
                    dojo.style('stp_count_' + notif.args.phase_arg, 'color', 'red');
                }
            },

            /*
             * Message for all updated player scores
             */
            notif_newScores: function (notif) {
                if (this.debug) {
                    console.log('notif new scores', notif);
                }

                for (const player_id in notif.args.scores) {
                    const newScore = notif.args.scores[player_id];
                    this.bga.playerPanels.getScoreCounter(parseInt(player_id)).toValue(newScore);
                    if (notif.args.rubles && this.player_rubles[player_id]) {
                        // End game only when rubles are traded for points
                        const newRubles = notif.args.rubles[player_id];
                        this.player_rubles[player_id].toValue(newRubles);
                    }
                }

            },

            /*
             * Message for discarding cards from board either
             * from new round or discarded Observatory or Debtor’s Prison draw
             */
            notif_discard: function (notif) {
                if (this.debug) {
                    console.log('notif discard', notif);
                }

                // Clear all pass
                this.enableAllPlayerPanels();

                const lastIndex = parseInt(notif.args.cards.length) - 1;
                for (const i in notif.args.cards) {
                    const card = notif.args.cards[i];
                    // Card location
                    const row = parseInt(card.row);
                    let col = card.col;
                    if (row === parseInt(this.constants.observatory)) {
                        // Observatory pick
                        col = 0;
                    }
                    if (row === parseInt(this.constants.discardRow)) {
                        document.getElementById(this.getCardDiv(this.constants.discardRow, DISCARDED_COL)).remove();
                        this.addCardOnBoard(this.constants.discardRow, DISCARDED_COL, parseInt(col));
                        if (this.discardStock.count() > 0) {
                            this.hideDiscardStock();
                        }
                    } else {
                        // Move to discard pile and destroy
                        const discardedCardId = this.getCardDiv(row, col);
                        const anim = this.slideToObject(discardedCardId, 'discard_pile');
                        dojo.connect(anim, 'onEnd', (node) => {
                            if (parseInt(i) !== lastIndex) {
                                dojo.destroy(node);
                            } else {
                                this.setAsLastDiscarded(discardedCardId);
                            }
                        });
                        anim.play();
                    }
                }
            },

            /*
             * Message for discarding a card from player table.
             */
            notifTableDiscard: function(notif) {
                if (this.debug) {
                    console.log('notifTableDiscard', notif);
                }
                // Remove discarded card from table.
                this.discardPlayerCard(notif.args.player_id, notif.args.card_id);
                if (notif.args.aristocrats !== undefined) {
                    this.player_aristocrats[notif.args.player_id].setValue(notif.args.aristocrats);

                }
                this.setIncome(notif.args.player_id, notif.args.income);
            },

            /**
             * Set a card built as 'MOVE_DISCARD_COL' as the last discarded card.
             * @param discardedCardDivId A discarded card div id.
             */
            setAsLastDiscarded: function (discardedCardDivId) {
                const newDivId = this.getCardDiv(this.constants.discardRow, DISCARDED_COL);
                if (this.debug) {
                    console.log('setAsLastDiscarded', discardedCardDivId, newDivId);
                }
                const previousDiscarded = document.getElementById(newDivId);
                if (null !== previousDiscarded) {
                    if (this.debug) {
                        console.log('setAsLastDiscarded', previousDiscarded);
                    }
                    // Destroy previous discarded card.
                    previousDiscarded.remove();
                }
                // Update card DOM id for new position
                dojo.attr(discardedCardDivId, 'id', newDivId);
                this.resetTooltip(discardedCardDivId, newDivId);
                const discardedCard = document.getElementById(newDivId);
                discardedCard.classList.add("stp_discarded");
                discardedCard.removeEventListener("click", this.onSelectCard);
                discardedCard.style.top = this.getCSSVariable('--stp-discard-top');
                discardedCard.style.left = this.getCSSVariable('--stp-discard-left');
            },

            /*
             * Message for new round starting
             */
            notif_newRound: function (notif) {
                if (this.debug) {
                    console.log('notif new round', notif);
                }

                // Animate rotation of starting player tokens
                this.setTokens(notif.args.tokens, true);

                // Reset Observatory cards to be usable
                dojo.query('.stp_maskcard').style('display', 'none');

                // Update score counters for any used
                for (const i in notif.args.observatory) {
                    this.player_income[notif.args.observatory[i]].points[1].incValue(1);
                }
            },

            /*
             * Message for player buying points with Pub
             */
            notif_buyPoints: function (notif) {
                if (this.debug) {
                    console.log('notif buy points', notif);
                }

                // Update score
                this.bga.playerPanels.getScoreCounter(notif.args.player_id).incValue(notif.args.points);

                if (this.player_rubles[notif.args.player_id]) {
                    // Active player sees ruble count after playing cost
                    // (either their own or with game option enabled for others)
                    this.player_rubles[notif.args.player_id].incValue(-notif.args.cost);
                }
            },

            /*
             * Message for player drawing a card with Observatory
             */
            notif_observatory: function (notif) {
                if (this.debug) {
                    console.log('notif observatory', notif);
                }

                // Observatory no longer scores this round
                this.player_income[notif.args.player_id].points[1].incValue(-1);
            },

            /*
             * Message to update counters of player having chosen guild hall scoring.
             */
            notifGuildHall: function (notif) {
                if (this.debug) {
                    console.log('notif Guild Hall', notif);
                }

                const playerId = parseInt(notif.args.player_id);
                this.bga.playerPanels.getScoreCounter(playerId).toValue(notif.args.score);
                if (notif.args.totalRubles !== undefined) {
                    this.player_rubles[playerId].toValue(notif.args.totalRubles);
                } else if (notif.args._private !== undefined) {
                    this.player_rubles[playerId].toValue(notif.args._private.totalRubles);
                }
            },

            /*
             * Message for end of game starting
             */
            notif_lastRound: function () {
                // In addition to log show message in game window
                this.showMessage(_('This is now the final round!'), 'info');
                dojo.style('stp_final_label', 'display', 'block');
            },

        });
    });
