# EchoMTG PHP Library

Basic wrapper for the [EchoMTG](http://echomtg.com) REST API to manage Magic: the Gathering card collections, prices, and values. See more information on API developments at [http://echomtg.com/api](http://echomtg.com/api).

This PHP library is currently in development/beta testing and provided as-is.

## Installation

Make sure you have an EchoMTG account (you can register for free). The API does not currently support OAuth so all authentication is done via encrypted posts.

Copy the `api.php` library file into your project and instantiate a new object:

    require 'api.php';

    $echomtg = new EchoPHP( 'foo@bar.com', 'password' );
    $echomtg->initSession();

Note: `initSession()` will check to see if your auth token has been saved to your current session; if it isn't, it posts to `/user/auth` to sign in you.

## Usage

Currently the only supported functions are adding cards individually and via bulk upload. To add a card individually, use the `addCard()` method:

    $echomtg->addCard( 4797, 1, 1.50, 0 );

The only required parameter is the first one, the card's [Mutiverse ID](http://gatherer.wizards.com), i.e., it's ID in Gatherer. You can get this by hand by searching for the card/printing there or using the [MTGJson](http://mtgjson.com) API (or similar services).

The other parameters are quantity, your purchase price, and whether the card is foil (1) or not (0).
