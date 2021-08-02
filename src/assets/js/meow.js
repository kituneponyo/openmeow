
const _m = {

    tl: '', // 現在選択中のTL
    isLTL: ()=> (_m.tl == 'p' || _m.tl == 'l'),
    isHTL: ()=> (_m.tl == 'h'),
    isPTL: ()=> (_m.tl == 'e'),

    q: '', // 検索語

    isExistsLatest: false,

    currentMeowId: 0,

    uploadFileNames: [],
    uploadFiles: [],

    config: {
    },

    cache: {
        meows: {},
        'p': {ids: [], lastUpdate: 0}, // LTL
        'l': {ids: [], lastUpdate: 0}, // HTL(old)
        'h': {ids: [], lastUpdate: 0}, // HTL(new)
        'e': {ids: [], lastUpdate: 0}, // private timeline
        'r': {ids: [], lastUpdate: 0}, // replies
        'u': {ids: [], lastUpdate: 0} // user
    },
    clearMeowCache: () => {
        _m.cache.p = {ids: [], lastUpdate: 0}; // LTL local timeline
        _m.cache.l = {ids: [], lastUpdate: 0}; // HTL home timeline
        _m.cache.h = {ids: [], lastUpdate: 0}; // HTL home timeline
        _m.cache.e = {ids: [], lastUpdate: 0}; // private timeline
        _m.cache.r = {ids: [], lastUpdate: 0}; // reply
        _m.cache.u = {ids: [], lastUpdate: 0}; // user
    },

    updateAt: Math.floor((new Date()).getTime() / 1000),
    updateLastUpdate: () => (_m.cache[_m.tl].lastUpdate = _m.updateAt = Math.floor((new Date()).getTime() / 1000)),

    toggleMenu: () => $('#user-menu-wrapper').toggle(),

    regex_url: /(\bhttps?:\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z\d+&@#\/%=~_|])/ig,
    regex_imgurl: new RegExp("https?://([a-zA-Z0-9_\\-/+%\\.!]+)?\\.(jpg|png|gif)", "ig"),
    regex_youtube: new RegExp("(youtube\.com/watch\\?v=|youtu.be/)([a-zA-Z0-9_\\-]+)?", "ig"),
    regex_nico: /https:\/\/((www|sp)?\.?nicovideo\.jp\/watch|nico\.ms)\/([a-z]m\d+)/ig,
    regex_twitter: /https:\/\/.*\.?twitter\.com/ig,
    regex_instagram: /https:\/\/www\.instagram\.com/ig,
    regex_amazon: /https:\/\/www\.amazon\.co\.jp.*?\/(dp|gp\/product)\/([^\/]+)?\/?.*$/ig,
    regex_ameblo: /https:\/\/ameblo.jp/ig,
    regex_hashtag: /(^|\s)(#([^#\s"]+))/ig,
    regex_reply_to: /(^|\s)(@([A-Z\d_]+))/ig,

    ignoreHosts: [
        'twitter.com',
        'amzn.to',
        'ameba.jp',
        'www.instagram.com',
        'goo.gl',
        'bit.ly'
    ],

    amazonIframeUrl: "//ws-fe.assoc-amazon.com/widgets/cm?lt1=_blank&o=9&p=8&l=as4&asins=$2&t=",

    useCrying: () => ($.cookie('useCrying') == "1"),
    postSound: new Audio('/assets/sound/wikimedia_commons_Meow.ogg.mp3'),
    angrySound: new Audio('/assets/sound/wikimedia_commons_Meow.ogg.mp3'),
    noticeSound: new Audio('/assets/sound/wikimedia_commons_Meow.ogg.mp3'),

    prevImage: null,
    nextImage: null,

    // 新着チェック
    checkLatestInterval: 120000,
    checkLatestTimer: null,
    setCheckLatestTimer: isContinue => {
        clearInterval(_m.checkLatestTimer); // いったんタイマー停止
        _m.checkLatestInterval = isContinue ? _m.checkLatestInterval + 120000 : 120000; // 新着がなかったら、次の確認を遅めていく
        _m.checkLatestTimer = setInterval(checkLatest, _m.checkLatestInterval);
    },

    // リプライチェック
    checkReplyInterval: 120000,
    checkReplyTimer: null,
    setCheckReplyTimer: isContinue => {
        _m.checkReplyInterval = isContinue ? _m.checkReplyInterval + 180000 : 120000;
        _m.checkReplyTimer = setTimeout(checkReply, _m.checkReplyInterval);
    },

    swipeStartX: 0,
    swipeStartY: 0,
    swipeEndX: 0,
    swipeEndY: 0,
    logSwipeStart: e => (_m.swipeStartX = _m.swipeEndX = e.touches[0].pageX),
    logSwipe: e => {
        (_m.swipeEndX = e.touches[0].pageX);
        const distanceX = _m.swipeEndX - _m.swipeStartX;
        if ((distanceX < 0 && _m.nextImage) || distanceX > 0 && _m.prevImage) {
            $('#image-viewer-image').css('left', 'calc(50% + ' + distanceX + 'px)');
        }
    },
    logSwipeEnd: e => {
        const distanceX = _m.swipeEndX - _m.swipeStartX;
        if (Math.abs(distanceX) > 50) {
            distanceX > 50 ? _m.toPrevImage() : _m.toNextImage();
        } else {
            $('#image-viewer-image').css('left', '50%');
        }
    },

    imageToCenter: ()=> ($('#image-viewer-image').animate({"left": '50%', "opacity": 1}, 200, "swing")),
    toNextImage: () => {
        if (_m.nextImage) {
            $('#image-viewer-image').css('opacity', 0);
            $('#image-viewer-image').css('left', '100%');
            _viewImage(_m.nextImage);
            _m.imageToCenter();
        }
    },
    toPrevImage: () => {
        if (_m.prevImage) {
            $('#image-viewer-image').css('opacity', 0);
            $('#image-viewer-image').css('left', '0%');
            _viewImage(_m.prevImage);
            _m.imageToCenter();
        }
    },

    isLazyLoad: false,
    lazyLoad: ()=>{
        if (_m.isLazyLoad) return false;
        _m.isLazyLoad = true;
        let $meowBox = $('.meow-box:visible:last'); // TLに表示されてる一番古いmeow
        let max = $meowBox.find('.meow-time').attr('title'); // 一番古いmeowのtimestamp
        return getLatest(_m.tl, 0, max); // 一番古いmeowよりも古い履歴をもってくる
    },
    setLazyLoad: ()=>{
        $(window).scroll(()=>{
            let $lastVisibleMeow = $('#meows div.meow-box:not(.mute):visible:last');
            if ($lastVisibleMeow.length
                && window.scrollY + window.innerHeight > $lastVisibleMeow.position().top
            ) {
                let $nextMeow = getNextMeow($lastVisibleMeow);
                if ($nextMeow && $nextMeow.length == 1){
                    $nextMeow.fadeIn(500);
                }
            }
            if ($('#meow-' + meow.lastMeowId).is(':visible')) {
                (window.scrollY + window.innerHeight > $('.meow-box:visible:last').position().top) && _m.lazyLoad();
            }
        });
    },

    paintToolNames: {
        1: 'paint with 8bitpaint',
        2: 'paint with BBS Paint NEO（しぃペインター）'
    },
    getPaintToolName: is_paint => (_m.paintToolNames[is_paint] ? _m.paintToolNames[is_paint] : ''),

    onClickUser: e => _m.toUserPage(getMeowBox(e).find('span.meow-user-meow-id').text().slice(1)),
    toUserPage: mid => window.location = '/u/' + mid,

    getUserPath: id => {
        id = id.toString().padStart(3, '0');
        return id.slice(-1) + "/" + id.slice(-2, -1) + "/" + id.slice(-3, -2);
    },

    getFileDir: t => '/up/' + t.substr(0, 4) + '/' + t.substr(5, 2) + '/' + t.substr(8, 2),

    redirect: url => (window.location = url) && false,

    muteUsers: [],
    muteWords: [],
    isUserMuted: userId => _m.muteUsers && _m.muteUsers.includes(userId),
    isWordMuted: text => _m.muteWords && _m.muteWords.some(w => text.includes(w)),

    insertNico: ($e, id) => $e.before('<iframe class="insert-nico" src="https://ext.nicovideo.jp/thumb/' + id + '" scrolling="no"></iframe>'),
    checkNico: $e => [...$e.text().matchAll(_m.regex_nico)].forEach(m => _m.insertNico($e, m[3])),

    insertImage: ($e, url) => $e.before('<div class="insert-image"><a href="' + url + '" target="_blank"><img src="' + url + '"></a></div>'),
    checkImage: $e => [...$e.text().matchAll(_m.regex_imgurl)].forEach(m => _m.insertImage($e, m[0])),

    isReplyMode: () => $('#meows #meow-post-box').length,

    closeCurtain: e => $(e.target).parent().hide(),

    // 表示してる meow の投稿時刻を更新
    updateDisplayTime: () => $('.meow-box:visible .meow-time').each((i, e) => $(e).text(_m.getDisplayTime($(e).attr('title')))),
    getDisplayTime: datetime => {
        let t = new Date(datetime.replace(/-/g,"/"));
        let diff = ((new Date()).getTime() - t.getTime()) / 1000;
        if (diff < 60) { // 60秒未満
            return '今';
        } else if (diff <= 3600) { // 60分未満
            return parseInt((diff % 3600) / 60) + "分";
        } else if (diff <= (24 * 3600)) { // 24時間未満
            return parseInt((diff % (3600 * 24)) / 3600) + "時間";
        } else if (diff <= (7 * 24 * 3600)) {
            return parseInt((diff % (3600 * 24 * 7)) / (3600 * 24)) + "日";
        } else if (diff <= (12 * 7 * 24 * 3600)) {
            return (t.getMonth() + 1) + '月' + t.getDate() + '日';
        }
        return t.getFullYear() + '年' + (t.getMonth() + 1) + '月' + t.getDate() + '日';
    },

    checkUpdate: v => {
        if (v && v != _m.version) {
            let msg = 'アップデートがありました。画面を更新します。\n現在のバージョン:' + _m.version + "\n新しいバージョン:" + v;
            if (confirm(msg)) {
                location.reload();
            }
        }
    }
};

_m.form = {
    open: () => {
        _m.isReplyMode() && resetMeowForm();
        $('#meow-post-box').show();
        $('#meow-post-box textarea').focus();
        $(window).scrollTop($('#meow-post-box').position().top);
    },
    close: () => {
        _m.isReplyMode() && resetMeowForm();
        $('#meow-post-box').hide();
    },
    isVisible: () => $('#meow-post-box').is(':visible'),
    toggle: () => (_m.form.isVisible() && !_m.form.isOverScroll()) ? _m.form.close() : _m.form.open(),
    isOverScroll: () => (window.scrollY > $('#meow-post-box').position().top)
};

_m.ogp = {
    check: ($text, url) => {
        let ogpCache = localStorage.ogp ? JSON.parse(localStorage.ogp) : {};
        if (ogpCache[url]) {
            let ogp = ogpCache[url];
            if (ogp.update_at) {
                _m.ogp.insert($text, ogp);
            } else {
                _m.ogp.get($text, url);
            }
        } else {
            _m.ogp.get($text, url);
        }
        // ついでにキャッシュ期限過ぎたやつを消していく
        _m.ogp.checkExpiredCache();
    },
    get: ($text, url) => {
        $.post("/api/getOgp", {'url': url})
            .done(data => {
                let ogp = JSON.parse(data);
                if (ogp.url) {
                    let ogpCache = localStorage.ogp ? JSON.parse(localStorage.ogp) : {};
                    ogpCache[url] = ogp;
                    if (!ogp.update_at) {
                        let now = new Date();
                        ogp.update_at = now.getFullYear() + "-" + (now.getMonth() + 1) + '-' + now.getDate() + ' ' + now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
                    }
                    localStorage.ogp = JSON.stringify(ogpCache);
                }
                _m.ogp.insert($text, ogp);
            });
    },
    insert: ($text, ogp) => {
        let html = $text.html();
        if (ogp.url && ogp.title) {
            let $ogpHtml = $('<div class="ogp"></div>');
            if (ogp.thumb) {
                $ogpHtml.append('<img src="' + ogp.thumb + '">');
            } else if (ogp.image) {
                $ogpHtml.append('<img src="' + ogp.image + '">');
            }
            $ogpHtml.append('<p class="ogp-title">' + ogp.title + '</p>');
            if (ogp.description) {
                $ogpHtml.append('<p class="ogp-description">' + ogp.description + '</p>');
            }
            $ogpHtml.append('<p class="clear"></p>');
            $ogpHtml.append('<p class="ogp-link"><a href="' + ogp.url + '" target="_blank">' + ogp.url + '</a></p>');
            html = html.replace(ogp.url, $ogpHtml.prop('outerHTML'));
        } else {
            html = html.replace(ogp.url, '<a href="' + ogp.url + '" target="_blank">' + ogp.url + '</a>');
        }
        $text.html(html);
    },
    checkExpiredCache: () => {
        let ogps = JSON.parse(localStorage.ogp);
        let urls = Object.keys(ogps);
        let now = (new Date()).getTime();
        urls.forEach(url => {
            let ogp = ogps[url];
            let diff = now - Date.parse(ogp.update_at);
            // 1週間
            if (diff > (3600 * 24 * 7)) {
                delete(ogps[url]);
                localStorage.ogp = JSON.stringify(ogps);
            }
        });
    }
};

_m.clearMeowCache();

function deleteMeow (id) {
    if (confirm('投稿を削除します。よいですか？')) {
        $.ajax('/post/delete', {method: 'post', data: {id: id}})
            .done(v => (v == "1") && $('#meow-' + id).remove() && alert('投稿を削除しました。'));
    }
    return false;
}

const onClickHeaderMeowIcon =()=> _m.form.toggle();
const onClickFootMeowIcon =()=> _m.form.toggle();

const toReplyDm = (mid) => window.open('dm/u/' + mid);
const _onClickToReplyDm = $t => toReplyDm($t.find('.meow-user-meow-id').text().substring(1));
const onClickToReplyDm = e => _onClickToReplyDm($(e.target).parents('.meow-box'));

const toReply = (mid, id) => window.open('/p/' + mid + '/' + id);
const _onClickToReply = $t => toReply($t.find('.meow-user-meow-id').text().substring(1), $t.attr('meowid'));

const onClickToReply = e => {
    _m.me.id
        ? setReplyForm($(e.target)) // ログインしてれば ajax でべろーんと出す
        : _onClickToReply($(e.target).parents('.meow-box')); // 非ログインなら個別meowページ開く
};

function fav(id) {
    if (!_m.me.id) return false;
    let method = $('#meow-' + id + ' .fav-on').length > 0 ? 'off' : 'on';
    $.ajax('/fav/' + method, {
        method: 'post',
        data: {id: id}
    })
        .done(data => {
            if (data == 1) {
                let $fav = $('#meow-' + id + ' .fav');
                let favCount = $fav.text() ? parseInt($fav.text()) : 0;
                if (method == 'on') {
                    $fav.addClass('fav-on').removeClass('fav-off');
                    favCount++;
                } else {
                    $fav.addClass('fav-off').removeClass('fav-on');
                    favCount--;
                }
                if ($('#meow-' + id + ' .meow-user-meow-id').text() == '@' + _m.me.mid) {
                    $fav.text(favCount > 0 ? favCount : '');
                }
            }
        });
    return false;
}

function queryFav () {
    if (!_m.me.id) return false;
    let ids = [];
    $('.fav:not(.fav-on):not(.fav-off)').each(function(i, e){
        let _id = $(e).parents('.meow-box').attr('meowid');
        _id && ids.push(_id);
    });
    if (ids.length == 0) return false;
    $.ajax('/fav/q', {
        method: 'post',
        data: {ids: ids}
    })
        .done(data => {
            let result_ids = JSON.parse(data);
            ids.forEach(id => {
                $('#meow-' + id + ' .fav')
                    .addClass(result_ids.includes(id) ? 'fav-on' : 'fav-off')
                    .on('click', onClickFav);
            });
        });
    return false;
}

function _popupYoutube ($e) {
    $('#youtube-viewer')
        .empty()
        .append(
            '<iframe class="youtube" '
            + ' src="https://www.youtube.com/embed/' + $e.attr('youtubeid') + '" '
            + ' frameborder="0" allow="encrypted-media" allowfullscreen '
            + ' width="560" height="315"></iframe> '
        );
    $('#youtube-viewer-wrapper').show();
}
const popupYoutube = e => _popupYoutube($(e.target));
function insertYoutube ($e, id) {
    return $e.before(
        '<div class="insert-youtube">' +
        '<img class="youtube-thumb" youtubeid="' + id + '" src="https://img.youtube.com/vi/' + id + '/mqdefault.jpg">' +
        '<img class="youtube-play-icon" src="/assets/icons/systemuicons/play_button_youtube.svg">' +
        '</div>'
    );
}
const checkYoutube = $e => [...$e.text().matchAll(_m.regex_youtube)].every(m => insertYoutube($e, m[2])); // matchすれば true, 1件もなければ false を返したい

function deleteNewPreview (e) {
    let $p = $(e.target).parent('.new-preview');
    let index = $p.attr('fileIndex');
    _m.uploadFiles[index] = '';
    _m.uploadFileNames[index] = '';
    $p.remove();
}

function appendFile (data) {
    let index = _m.uploadFiles.length;
    _m.uploadFiles[index] = data;
    if (_m.uploadFiles.length > _m.uploadFileNames.length) {
        _m.uploadFileNames.push('');
    }
    let $newImg = $(document.createElement('img'));

    if (data.includes('data:audio/mid;')) {
        $newImg.attr('src', '/img/midi_icon.svg');
    } else {
        $newImg.attr('src', data);
    }

    let $closeBtn = $(document.createElement('div'))
        .addClass('close-btn')
        .on('click', deleteNewPreview);
    let $newPreview = $(document.createElement('div'))
        .addClass('new-preview')
        .attr('fileIndex', index)
        .append($newImg)
        .append($closeBtn);
    $('#new-previews').append($newPreview);
}

const onLoadFile = e => appendFile(e.target.result);

function onMeowFormReadImgFile () {
    $('#img1').on('change', e => {
        let files = Array.from(document.getElementById('img1').files);
        if (files.length > 0) {
            files.forEach(file=>{
                let _reader = new FileReader();
                _reader.onload = onLoadFile;
                _m.uploadFileNames.push(file.name);
                _reader.readAsDataURL(file);
            });
        }
    });
}

function onMeowTextAreaPasteImage () {
    let element =  document.querySelector("[contenteditable]");
    // chrome向け
    element.addEventListener("paste", function(e){

        // 画像の場合以外を弾く
        if (!e.clipboardData
            || !e.clipboardData.types
            || e.clipboardData.types.indexOf('Files') == -1
        ) {
            return true;
        }

        // ファイルとして得る
        let fileIndex = e.clipboardData.types.indexOf('Files');
        let imageFile = e.clipboardData.items[fileIndex].getAsFile();

        // FileReaderで読み込む
        let fr = new FileReader();
        // onload内ではe.target.resultにbase64が入っている
        fr.onload = function(e) {
            appendFile(e.target.result);
        };
        fr.readAsDataURL(imageFile);
        $('#img1').val('');
    });

    // Firefox,IE向け
    element.addEventListener("input", function(e){
        // 仮のエレメントを作り、張り付けた内容にimgタグがあるか探す
        let temp = document.createElement("div");
        temp.innerHTML = this.innerHTML;
        let pastedImage = temp.querySelector("img");

        // イメージタグがあればsrc属性からbase64が得られる
        pastedImage && appendFile(pastedImage.src);
    })
}

function onMeowTextAreaSubmitWithCtrlAndEnterKey () {
    //テキストエリアがアクティブ時にキーが押されたらイベントを発火
    $('#meow-post-box textarea').keydown(e => {
        //ctrlキーが押されてる状態か判定
        if (e.ctrlKey) {
            //押されたキー（e.keyCode）が13（Enter）か　そしてテキストエリアに何かが入力されているか判定
            if (e.keyCode === 13) {
                console.log('1');
                console.log($(e.target).val());
                if ($(e.target).val()) {
                    //フォームを送信
                    //$('#meow-post-form').submit();
                    return postMeow();
                }
            }
        }
    });
    return false;
}

const getMeowBox = e => $(e.target).parents('.meow-box');
const getMeowIdByElement = e => $(e.target).parents('.meow-box').attr('meowid');

const onClickDeleteMeow = e => deleteMeow(getMeowIdByElement(e));

const showSensitive = id => {
    // meow-is-sensitive は remove しちゃだめ
    $('#meow-' + id + ' .sensitive-cussion').hide();
    $('#meow-' + id + ' .meow-body').show();
    return false;
};
const onClickShowSensitive = e => showSensitive(getMeowIdByElement(e));

const hideSensitive = id => {
    $('#meow-' + id + ' .sensitive-cussion').show();
    $('#meow-' + id + ' .meow-body').hide();
    return false;
};
const onClickHideSensitive = e => hideSensitive(getMeowIdByElement(e));

const onClickFav = e => fav(getMeowIdByElement(e));
const onClickShare = e => selectShare(getMeowIdByElement(e));

const onClickHashtag = e => searchTag(e.target.innerText);

const url2link = ($e, url) => $e.html($e.html().replace(url, '<a href="' + url + '" target="_blank">' + url + '</a>'));

// 各種設定
function decorateMeow ($m) {

    if (!localStorage.ogp) {
        localStorage.ogp = [];
    }

    if ($m.hasClass('meow-is-sensitive')) {
        $m.find('.show-sensitive input').on('click', onClickShowSensitive); // センシティブ表示
        $m.find('.hide-sensitive input').on('click', onClickHideSensitive); // センシティブ隠す
    }
    $m.find('.meow-foot-fav a').on('click', onClickFav); // いいね
    $m.find('.meow-foot-reply-count').on('click', onClickToReply); // 返信
    $m.find('.meow-foot-reply-dm').on('click', onClickToReplyDm); // DM返信
    $m.find('.meow-foot-share').on('click', onClickShare); // shareメニュー
    $m.find('.meow-foot-menu').on('click', onClickMeowMenu); // メニュー

    $m.find('div.icon').on('click', _m.onClickUser); // アイコンクリック

    // テキストがある場合の処理
    let $text = $m.find('p.meow-text');
    let html = $text.html();
    if (html) {

        let hasHtmlTag = html.match('<'); // activitypub の remote note の場合、最初からタグが入ってる

        if (hasHtmlTag) {

            // remote note

            let text = $text.text();

            [...text.matchAll(_m.regex_url)].forEach(match => {
                let url = match[0];
                if (url.match(_m.regex_imgurl)) {
                    _m.insertImage($text, url);
                } else if (url.match(_m.regex_youtube)) {
                    [...url.matchAll(_m.regex_youtube)].forEach(match => insertYoutube($text, match[2]));
                    $m.find('img.youtube-thumb').on('click', popupYoutube);
                    $m.find('img.youtube-play-icon').on('click', e => _popupYoutube($(e.target).prev()));
                } else if (url.match(_m.regex_nico)) {
                    [...url.matchAll(_m.regex_nico)].forEach(match => _m.insertNico($text, match[3]));
                }
            });

        } else {

            [ ... html.matchAll(_m.regex_url)].forEach(match => {
                let url = match[0];
                let segments = url.split('/');
                if (url.match(_m.regex_imgurl)) {
                    _m.insertImage($text, url);
                    url2link($text, url);
                } else if (url.match(_m.regex_youtube)) {
                    [...url.matchAll(_m.regex_youtube)].forEach(match => insertYoutube($text, match[2]));
                    $m.find('img.youtube-thumb').on('click', popupYoutube);
                    $m.find('img.youtube-play-icon').on('click', e => _popupYoutube($(e.target).prev()));
                    url2link($text, url);
                } else if (_m.ignoreHosts.includes(segments[2])) {
                    url2link($text, url);
                } else if (url.match(_m.regex_nico)) {
                    [...url.matchAll(_m.regex_nico)].forEach(match => _m.insertNico($text, match[3]));
                    url2link($text, url);
                } else if (url.match(_m.regex_amazon)) {
                    let dp = url.replace(_m.regex_amazon, "https://www.amazon.co.jp/dp/$2");
                    $text.html($text.html().replace(url, '<a href="' + dp + '" target="_blank">' + dp + '</a>'));
                    $text.append(url.replace(_m.regex_amazon, "<div class='insert-amazon'><iframe scrolling=no src='" + _m.amazonIframeUrl + "'></iframe></div>"));
                } else if (!hasHtmlTag) {
                    // とりあえず remote note でなければ ogp 対応
                    // checkOgp($text, url);
                    _m.ogp.check($text, url);
                }
            });
        }

        html = $text.html();

        // if (!hasHtmlTag) {
        //     html = html.replace(_m.regex_url, '<a href="$1" target="_blank">$1</a>');
        // }

        html = html
            .replace(_m.regex_reply_to, '$1<a href="/u/$3" target="_blank">@$3</a>')
            .replace(_m.regex_hashtag, '$1<a class="hashtag" href="#">$2</a>')
            .replace(/\n/g, '<br>');

        $text.html(html);

        $m.find('a.hashtag').on('click', onClickHashtag);
    }

    // 画像タグでimage-viewer起動
    $m.find('div.insert-image img').on('click', viewImage);

    // midi
    $m.find('p.meow-midi').on('click', openMidiBox);

    $m.removeClass('meow-box-raw');

    return $m;
}

// まとめて各種設定
const decorateMeows =()=> $('div.meow-box-raw').each((i, e) => decorateMeow($(e)));

const bookmarkOn = $a => $a.removeClass('bookmark-off').addClass('bookmark-on');
const bookmarkOff = $a => $a.removeClass('bookmark-on').addClass('bookmark-off');
const bookmark = id => changeBookmark(id, !$('#meow-' + id + ' .bookmark').hasClass('bookmark-on'));

const addBookmark = id => changeBookmark(id, 1);
const removeBookmark = id => changeBookmark(id, 0);
function changeBookmark (id, v) {
    const $a = $('#meow-' + id + ' .bookmark');
    const album_id = $('select[name=album_id]').length ? $('select[name=album_id]').val() : 0;
    const params = {
        type: 'post',
        data: { meow_id: id, album_id: album_id }
    };
    $.ajax('/bookmark/' + (v ? 'on' : 'off'), params)
        .done(data => (data == '1' && (v ? bookmarkOn($a) : bookmarkOff($a))));
    return false;
}

function popupBookmarkMenu (id) {
    _m.currentMeowId = id;
    let $m = $('#meow-' + id + ' .bookmark');
    $m.hasClass('bookmark-on') ? $('#bookmark-menu-remove').show() : $('#bookmark-menu-remove').hide();
    $('#bookmark-menu-wrap').show();
    return false;
}
const closeBookmarkMenu =()=> $('#bookmark-menu-wrap').hide() && false;

const onClickBookmark = e => popupBookmarkMenu(getMeowIdByElement(e));

function queryBookmark () {
    if (!_m.me.id) return false;
    let ids = [];
    $('.bookmark:not(.bookmark-on):not(.bookmark-off)').each((i, e) => {
        let _id = $(e).parents('.meow-box').attr('meowid');
        _id && ids.push(_id);
    });
    if (ids.length == 0) return false;
    $.ajax('/bookmark/q', {
        method: 'post',
        data: {ids: ids}
    })
        .done(data => {
            let result_ids = JSON.parse(data);
            ids.forEach(id => {
                $('#meow-' + id + ' .bookmark')
                    .addClass(result_ids.includes(id) ? 'bookmark-on' : 'bookmark-off')
                    .on('click', onClickBookmark);
            });
        });
    return false;
}

const getMeowById = id => $('#meow-' + id);
const getCurrentMeow =()=> getMeowById(_m.currentMeowId);

function copySelectedMeowUrl () {
    let $m = getCurrentMeow();
    let mid = $m.find('.meow-user-meow-id').text().slice(1);
    let meowid = $m.attr('meowid');
    let url = 'https://' + location.host + '/p/' + mid + '/' + meowid;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(
            v => alert('URLをコピーしました。'),
            w => alert('error')
        );
    } else {
        alert('このブラウザには対応していません。。。');
    }
    $('#select-share-wrapper').hide();
    return false;
}
function shareSelectedMeowToTwitter () {
    let $m = getCurrentMeow();
    let mid = $m.find('.meow-user-meow-id').text().slice(1);
    let meowid = $m.attr('meowid');
    let text = encodeURI($m.find('.meow-text').text().slice(0, 128));
    let name = $m.find('.meow-user-name').text();
    let okuduke = _m.me.mid != mid ? '+-+meow+(' + name + ')%0A' : '';
    let url = 'https://twitter.com/share?url=https://' + location.host + '/p/' + mid + '/' + meowid + '&text=' + text + okuduke;
    window.open(url, '_blank');
    $('#select-share-wrapper').hide();
    return false;
}

const selectShare = meowid => (_m.currentMeowId = meowid) && $('#select-share-wrapper').show();

const selectPainter =()=> $('#select-painter-wrapper').show();

function open8bitpaint () {
    $('#transfer-8bitpaint-text').val($("#meow-post-text").val());
    $('#transfer-8bitpaint-form input[name=reply_to]').val($('#meow-post-form input[name=reply_to]').val());
    $('#transfer-8bitpaint-form').submit();
    return false;
}
function openBbsPaintNeo () {
    $('#transfer-bbspaintneo-text').val($("#meow-post-text").val());
    $('#transfer-bbspaintneo-form input[name=reply_to]').val($('#meow-post-form input[name=reply_to]').val());
    $('#transfer-bbspaintneo-form').submit();
    return false;
}

// iOSの音声再生ロック解除
function unlockApplePlaySound () {
    _m.postSound.play();
    document.removeEventListener('click', unlockApplePlaySound);
}

function onSearchFormSubmit() {
    meow.searchWord = _m.q; // 実際に検索されたワードを保存
    _m.updateAt = 0;
    _m.isExistsLatest = true;
    _m.clearMeowCache();
    return getLatestDiff();
}

const toggleSearchBox =()=> $('#search-box').is(':visible') ? closeSearchBox() : openSearchBox();
function openSearchBox () {
    $('#search-box').show()
        .animate({'height': "70px", 'opacity': 1}, 300, 'swing')
        .removeClass('search-box-close');
    return false;
}
function closeSearchBox () {
    let $s = $('#search-box')
        .addClass('search-box-close')
        .animate({'height': "0px", 'opacity': 0}, 300, 'swing');
    setTimeout(()=>$s.hide(), 300);
    return false;
}
function onClearSearchWordClick () {
    if (meow.searchWord) {
        _m.q = '';
        $('#search-text').val('');
        _m.updateAt = 0;
        _m.isExistsLatest = true;
        _m.clearMeowCache();
        search('');
    }
    return closeSearchBox();
}
function searchAjax (q) {
    $('#search-text').val(q);
    $('#search-form').submit();
    return openSearchBox();
}
function search (q) {
    meow.searchWord = q; // 実際に検索したワードを保存しておく
    return _m.tl ? searchAjax(q) : _m.redirect("/?tl=p&q=" + encodeURI(q));
}
function searchTag (t) {
    _m.q = t;
    return _m.tl ? searchAjax(t) : _m.redirect("/?tl=p&t=" + encodeURI(t).replace('#', ''));
}

const marshmallows = [
    'ねこはすきですか',
    'ねこのはなしをしませんか',
    'ねこのよさをかたりませんか',
    'らいせはねこになりますか',
    'どんなねこになりたいですか',
    'ねこのどのぶぶんがすきですか',
    'ねこはなんとなきますか',
    'ねこじたですか',

    'そらはなにいろですか',
    'かぜはどんなにおいですか',

    'ふとんはすきですか',
    'さいきんどこにいきましたか',
    'さいきんなにがおいしかったですか',
    'さいきんいいことしましたか',
    'さいきんしゅみはありますか',
    'どんなゆめをみましたか',
    'とくぎはなんですか',

    'たべたいものはありますか',
    'いきたいばしょはありますか',
    'みんなにききたいことはありますか',
    'きになるおみせはありますか',
    'ゆめはありますか',
    'やすみのひはどうすごしてますか',
    'あたらしいことにちょうせんしてますか',
    'たからものはなんですか',

    'なにいろがすきですか',
    'すきなまんがはなんですか',
    'すきなえいがはなんですか',
    'すきなすしねたはなんですか',
    'すきなおんがくはなんですか',
    'すきなひとはいますか',
    'すきなおちゃはなんですか',
    'すきなさかなはなんですか',
    'すきなくだものはなんですか',
    'すきなやさいはなんですか',
    'すきなおかしはなんですか',
    'どんなふうけいがすきですか',
    'どんなにおいがすきですか',
    'かってよかったものはありますか',
    'やまとうみどっちがすきですか',
    'なつとふゆどっちがすきですか',
    'ごはんとぱんどっちがすきですか',
    'からいものたべれますか',

    'ねるまえにはみがきしましたか',

    'こうきしんにころされますか',
    'いま、なんかいめのいのちですか',
    'そろそろしっぽがふたつになりますか'
];

const getRandomMarshmallow = () => marshmallows[Math.floor(Math.random() * marshmallows.length)];
const setRandomMarshmallow = () => $('#meow-post-text').attr('placeholder', getRandomMarshmallow());

const viewImage = e => _viewImage($(e.target));
function _viewImage ($img) {
    if ($img) {
        let $insertImg = $img.parents('.insert-image');

        let $prev = $insertImg.prev('.insert-image');
        $prev.length ? $('#image-viewer-prev').show() : $('#image-viewer-prev').hide();
        _m.prevImage = $prev.length ? $prev.find('img') : null;

        let $next = $insertImg.next('.insert-image');
        $next.length ? $('#image-viewer-next').show() : $('#image-viewer-next').hide();
        _m.nextImage = $next.length ? $next.find('img') : null;

        $('#image-viewer').show();
        $('#image-viewer img').attr('src', $img.parent().attr('href'));
    }
    return false;
}

const closeImageViewer =()=> $('#image-viewer').hide();

function onClickMeowMenu (e) {
    let $meowBox = $(e.target).parents('.meow-box');
    _m.currentMeowId = $meowBox.attr('meowid');
    let is_private = $meowBox.attr('is_private');
    if (is_private == 3) {
        $('#meow-menu-to-public').show();
        $('#meow-menu-to-private').hide();
    } else if (is_private == 0) {
        $('#meow-menu-to-public').hide();
        $('#meow-menu-to-private').show();
    }
    if ($meowBox.hasClass('meow-is-sensitive')) {
        $('#meow-menu-to-sensitive').hide();
        $('#meow-menu-to-not-sensitive').show();
    } else {
        $('#meow-menu-to-sensitive').show();
        $('#meow-menu-to-not-sensitive').hide();
    }
    $('#meow-menu-wrap').show();
}

const closeMeowMenu =()=> $('#meow-menu-wrap').hide() && false;

function setMeowIsPrivate ($m, isPrivate) {
    $m.attr('is_private', isPrivate);
    _m.cache.meows[_m.currentMeowId] && (_m.cache.meows[_m.currentMeowId].is_private = isPrivate);
}
function removeMeowCacheOrder(tl, id) {
    const index = _m.cache[tl].ids.findIndex(n => n === id);
    _m.cache[tl].ids.splice(index, 1);
}

function meowToPublic () {
    let $m = $('#meow-' + _m.currentMeowId);
    setMeowIsPrivate($m, "0");
    if (_m.cache.meows[_m.currentMeowId]) {
        removeMeowCacheOrder("e", _m.currentMeowId);
        removeMeowCacheOrder("l", _m.currentMeowId);
        _m.cache["p"].ids.push(_m.currentMeowId);
    }
    $m.find('.meow-foot-only-each-follow').remove();
    $m.find('.meow-foot-menu').before(
        '<div class="meow-foot-item meow-foot-share"><div class="share"></div></div>'
    );
    $m.find('.meow-foot-share').on('click', onClickShare);
}
function onClickMeowToPublic () {
    $.post('/api/changeMeowToPublic', { id: _m.currentMeowId })
        .done(v => (v == "1" && meowToPublic()));
    return closeMeowMenu();
}
function meowToPrivate () {
    let $m = $('#meow-' + _m.currentMeowId);
    setMeowIsPrivate($m, "3");
    if (_m.cache.meows[_m.currentMeowId]) {
        removeMeowCacheOrder("p", _m.currentMeowId);
        _m.cache["l"].ids.push(_m.currentMeowId);
        _m.cache["e"].ids.push(_m.currentMeowId);
    }
    $m.find('.meow-foot-share').remove();
    $m.find('.meow-foot-menu').before(
        '<div class="meow-foot-item meow-foot-only-each-follow">' +
        '  <img src="/assets/icons/systemuicons/lock.svg" title="相互フォロー限定">' +
        '</div>'
    );
}
function onClickMeowToPrivate () {
    $.post('/api/changeMeowToPrivate', { id: _m.currentMeowId })
        .done(data => (data == "1" && meowToPrivate()));
    return closeMeowMenu();
}

function meowToSensitive (isSensitive) {
    let $m = $('#meow-' + _m.currentMeowId);
    $m.attr('is_sensitive', isSensitive);
    _m.cache.meows[_m.currentMeowId] && (_m.cache.meows[_m.currentMeowId].is_sensitive = isSensitive);
    isSensitive ? $m.addClass('meow-is-sensitive') : $m.removeClass('meow-is-sensitive');
    $m.find('.sensitive-cussion').css('display', isSensitive ? 'block' : 'none');
    $m.find('.meow-body').css('display', isSensitive ? 'none' : 'block');
    return false;
}
function onClickMeowToSensitive (v = 1) {
    $.post('/api/changeMeowToSensitive', { id: _m.currentMeowId, v: v })
        .done(data => (data == "1" && meowToSensitive(v)));
    return closeMeowMenu();
}

function openMidiBox (e) {
    if ($('#midi-box-scripts script').length == 0) {
        $('#midi-box-scripts').append(
            '<script src="/js/webaudio-tinysynth/webaudio-controls.js" />'
            + '<script src="/js/webaudio-tinysynth/webaudio-tinysynth.js" />'
        );
    }
    let $t = $(e.target);
    let $meowMidi = $t.hasClass('meow-midi') ? $t : $t.parents('.meow-midi');
    let file = $meowMidi.attr('file');
    let orgfile = $meowMidi.attr('orgfile');
    let $mb = $t.parents('.meow-box');
    let filePath = _m.getFileDir($mb.find('.meow-time').attr('title')) + "/" + file;
    $('div#midi-player').empty().append('<webaudio-tinysynth id="tinysynth" src="' + filePath + '" />');
    $('#midi-box-title').text(orgfile);
    $('#midi-box').attr('file', filePath);
    $('#midi-box-download a').attr('href', filePath).attr('download', orgfile);
    $('#midi-box-wrapper').show();
    return false;
}
const onClickMidiCurtain =()=> document.getElementById('tinysynth').stopMIDI();

function resetMeowForm () {
    // #meows の中にいたら .header の下に戻す
    if (_m.isReplyMode()) {
        let $meowPostBox = $('#meow-post-box').remove();
        $meowPostBox.find('input[name=reply_to]').val('');
        $('.header').after($meowPostBox);

        // イベントハンドラつけなおし
        setPostFormEventHandler();
    }
    return false;
}

// TLのリプライアイコンクリック
function setReplyForm ($e) {

    let $parent = $e.parents('.meow-box'); // リプライしたいmeow
    let id = $parent.attr('meowid'); // リプライしたいmeowのid

    // リプライフォームが既に存在する
    if ($('#meow-replies-at-' + id + ' > #meow-post-box').length > 0) {
        // 同じリプライアイコンをクリックした場合、表示・非表示切り替え
        return $('#meow-post-box').toggle() && false;
    }

    let $replyWrapper = $('#meow-replies-at-' + id);
    if ($('#meow-replies-at-' + id).length) {
        // リプライフォームが行ったり来たりしてて、リプライラッパが既にある場合、前後するので後ろに入れなおす
        $replyWrapper.remove();
        $parent.after($replyWrapper);
    } else {
        $parent.after('<div class="meow-reply" id="meow-replies-at-' + id + '"></div>');
        $replyWrapper = $('#meow-replies-at-' + id);
    }

    let $form = $('#meow-post-box').remove();
    $form.find('input[name=reply_to]').val(id);

    $replyWrapper.append($form);
    $form.show();

    // 追加したフォームにイベントハンドラ再セット
    setPostFormEventHandler();

    $('.meow-reply-at-' + id).remove();

    // リプライ読み込み
    $.ajax("/api/getMeowReplies", {
        method: "POST",
        data: {id: id}
    }).done(data=>{
        let replies = JSON.parse(data);
        if (!Array.isArray(replies) || replies.length == 0) {
            return false;
        }
        replies.forEach(reply=>{
            reply.reply_to = '';
            let $meowBox = createMeowBox(reply);
            $meowBox.addClass('meow-reply-at-' + id);
            if (!$meowBox.hasClass('mute')) {
                $form.after($meowBox);
            }
        });
    });
}


$(()=> {

    // _m に諸々追加
    onMeowJsLoadComplete();

    // 投稿フォームがなければ投稿フォーム開くボタン消す
    if (!$('#meow-post-box').length) {
        $('.meow-open').remove();
        $('#foot-meow-open').remove();
    }

    // インサート関連
    decorateMeows();

    if (_m.me.id) {

        // ふぁぼ状況取得
        ($('.fav').length > 0) && queryFav();

        // ブックマーク状況取得
        ($('.bookmark').length > 0) && queryBookmark();

        // 音声ロック解除（特にiOS）
        //_m.useCrying() && document.addEventListener('click', unlockApplePlaySound);

        // マシュマロ拒否してなければ設定
        ($.cookie('withoutMarshmallow') != '1') && setRandomMarshmallow();

        // フォントサイズ設定
        $('.meow-text').css('fontSize', ($.cookie('fontSize') ? $.cookie('fontSize') : 'medium'));

        // 人生に疲れた
        if ($.cookie('useZenjido')) {
            $('*').css('fontFamily', "'nekoneco', 'zenjido', 'osaka'");
            $('.meow-box *').css('fontSize', 'large');
        }
    }

    // keydown
    $(document).keydown(e => {
        // 画像ビュワー 左右キーで次・前画像に移動
        if ($('#image-viewer').is(':visible')) {
            if (e.key == 'ArrowRight') {
                _m.toNextImage();
            } else if (e.key == 'ArrowLeft') {
                _m.toPrevImage();
            }
        }
    });

    // カーテンをクリックするとポップアップを閉じる
    $('.curtain').on('click', _m.closeCurtain);

    // フリック、タッチ開始、タッチ終了
    $('#image-viewer')
        .on('touchmove', _m.logSwipe)
        .on('touchstart', _m.logSwipeStart)
        .on('touchend', _m.logSwipeEnd);

    // 1分ごとに表示している meow の時間表示を更新
    setInterval(_m.updateDisplayTime, 60000);
});