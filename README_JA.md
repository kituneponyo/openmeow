<p>
    <a href="./README.md">README.md (English version) is here</a>
</p>

<img src="https://opensource.meow.fan/assets/img/screen_sample.png">

# Meow

Meowは、穏やかさを大事にする人々のための小規模なSNSです。

快適なコミュニケーションとともに、心を波立たせないために、Meowにはさまざまな工夫があります。

# Feature

Meowでは、他人のフォロワー数や、いいねの数は見えません。
ユーザーは、表面的な数字の比較によって心を消耗する心配がありません。
人間にとって本当に大事な、自分の感情や理性に注視して、より人間的なコミュニケーションを行うことができます。

Meowには、retweetやトレンドのような、buzzを促進する機能がありません。
注目を浴びることを目的とした、過度に刺激的な発言を抑制するとともに、すべてのユーザーに、穏やかなタイムラインを提供します。

- 小さな(そして、まったく不完全な) ActivityPub サーバー

Meowは部分的にActivityPubをサポートします。
ユーザーは、Fediverseに住む人々と、必要最低限の会話が行えます。

Meowは、Fediverseに存在する、他の多くのソフトウェアより、比較的簡単に設置することができます。
必要なものは、PHP, MySQL, SSL だけで、root権限のない安価な共有レンタルサーバに設置できます。
Fediverseに興味のある、技術者でない普通の人々に対して、Meowは一つの選択肢を提案できるかもしれません。

Meowは生まれたばかりの貧弱なソフトウェアです。
充実した機能や、多くのユーザーを抱える活発なサーバーを運営したい場合は、もっと良い選択肢があるでしょう。

# Meowtles

Meowtlesは、@reifusen_chan@meow.fan によって創作され、@midorijp@meow.fan に多く描かれ続ける、Meowの非公式(ですが、事実上の公式）マスコットです。

<img src="https://opensource.meow.fan/assets/img/meowtles/22563.gif">

# Demos

オリジナルの Meow は、以下で稼働しています。

https://meow.fan/

依存関係などを整理したオープンソース版のMeowのdemoは以下にあります。

https://opensource.meow.fan/

# Requirements

* PHP7.2+
* MySQL5.5+
* SSL

# Installation

* MySQLのデータベースと、権限を与えたユーザを用意してください。
* ダウンロードしたファイルを、あなたのサーバに配置してください。
* https://（あなたのサーバ）/init を実行し、画面の案内に従ってください。

# Notes

現状、後方互換は考慮していません。
アップグレードの際はDB周りなど注意してください。

# Auther

<a href="https://meow.fan/u/k" target="_blank">kituneponyo</a>

# License

Meowのオリジナルのコードは MIT License により提供されます。
バンドルされた第三者によるソフトウェアやファイルについては、それぞれのライセンスにより提供されます。