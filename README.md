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

This wrapper class is in development, check below for the currently supported api calls.

### Managing Inventory

#### Adding cards to inventory `[POST]`

To add a card individually, use the `addCard()` method:

    $echomtg->addCard( 4797, 1, 1.50, '08-20-2015', 0 );

The only required parameter is the first one, the card's [Mutiverse ID](http://gatherer.wizards.com), i.e., it's ID in Gatherer. You can get this by hand by searching for the card/printing there or using the [MTGJson](http://mtgjson.com) API (or similar services).

The other parameters are quantity, your purchase price, the date of your purchase in mm-dd-yyyy format, and whether the card is foil (1) or not (0).

#### Removing cards from the inventory `[PUT]`

To remove a card by the inventory ID, use `removeCard()`. It only takes the ID of the card in your inventory.

#### Adjusting the acquisition price `[PUT]`

To adjust the acquisition price of a card in inventory, call `adjustAcquiredPrice()`, passing in the card's inventory ID in your inventory and the price you want to set.

#### Toggling foil status `[PUT]`

Set a card in inventory as a foil (1) or not foil (0) by calling `toggleFoil()`, passing in the card's inventory ID and the foil boolean.

#### Adjusting the acquisition date `[PUT]`

To adjust the acquisition date of a card in inventory, call `adjustAcquiredDate()`, passing in the card's inventory ID and the date you want to set in `MM-DD-YYYY` format (`m-d-Y` in PHP `date()` lingo).

### Viewing Inventory

#### Getting the inventory `[POST]`

Use the `getInventory()` method to return the user's inventory. The only parameters are start and end values to limit the query results. E.g., the following returns the most recent 10 cards:

    $echomtg->getInventory( 0, 9 );

## Debugging

To enable debugging mode, set the class property `$debug_mode` to true in `api.php`. This adds all auth, session, and requests/responses to the `$debug` property. Output that to view the current debugging log:

    print_r( $echomtg->debug );
