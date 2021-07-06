<p>
    <a href="./README_JA.md">README_JA.md (Japanese version) is here</a>
</p>

<img src="https://opensource.meow.fan/assets/img/screen_sample.png">

# Meow

Meow is a small social network for people who value serenity.

In order to keep your mind from wavering along with comfortable communication, Meow has a number of innovations.

# Feature

On Meow, you can't see how many followers others have, or how many likes they have.
Users don't have to worry about their minds being consumed by superficial comparisons of numbers.
They can focus on their own emotions and reasoning, which is really important for human beings, and communicate in a more human way.

Meow does not have any buzz-promoting features like retweeting or trending.
It provides a calm timeline for all users, while discouraging overly stimulating remarks aimed at gaining attention.

- A small (and totally incomplete) ActivityPub server

Meow partially supports ActivityPub.
It allows users to have minimal conversations with people living in the Fediverse.

Meow is relatively easier to set up than most of the other software that exists in the Fediverse.
All you need is PHP, MySQL, and SSL, and you can set it up on an inexpensive shared rental server with no root privileges.
For non-technical people who are interested in the Fediverse, Meow may offer an alternative.

Meow is a poorly developed piece of software.
If you want to run a full-featured, active server with a large number of users, there are better options.

# Meowtles

Meowtles is the unofficial (but de facto official) mascot of Meow, created by @reifusen_chan@meow.fan and continued to be drawn a lot by @midorijp@meow.fan.

<img src="https://opensource.meow.fan/assets/img/meowtles/22563.gif">

# Demos

The original Meow is running at

https://meow.fan/

A demo of the open-source version of Meow, with dependencies and so on sorted out, is available at

https://opensource.meow.fan/

# Requirements

* PHP7.2+
* MySQL5.5+
* SSL

# Installation

* Prepare a MySQL database and a user with permissions.
* Place the downloaded file on your server.
* Run https://(your server)/init and follow the on-screen instructions.

# Notes

Currently, backward compatibility is not considered.
When upgrading, please pay attention to the DB area.

# Auther

<a href="https://meow.fan/u/k" target="_blank">kituneponyo</a>

# License

The original code of Meow is provided under the MIT License.
Bundled software and files by third parties are provided under their respective licenses.
