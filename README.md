# EchoMTG PHP Library
Basic wrapper for the [EchoMTG](https://www.echomtg.com) REST API to manage Magic: the Gathering card collections, prices, and values. See more information on API developments at [https://www.echomtg.com/api](https://www.echomtg.com/api).

This PHP library is currently in development/beta testing and provided as-is.

## Installation

Make sure you have an EchoMTG account (you can register for free). The API does not currently support OAuth so all authentication is done via encrypted posts.

Copy the `config.ini` file and rename it `config.local.ini`, then set the account email and password there. Make sure the `api.php` library file is in the same folder as the .ini file in your project and instantiate a new object:

    require 'api.php';

    $echomtg = new EchoPHP();
    $echomtg->initSession();

Note: `initSession()` will check to see if your auth token has been saved to your current session; if it isn't, it posts to `/user/auth` to sign you in.

## Usage

This wrapper class is in development, check below for the currently supported API calls.

### Managing Inventory

#### Adding cards to inventory

To add a card individually, use the `addCard()` method:

    $echomtg->addCard( 4797, 1, 1.50, '08-20-2015', 0 );

The only required parameter is the first one, the card's [Mutiverse ID](http://gatherer.wizards.com), i.e., it's ID in Gatherer. There are two ways to get this:

1. The preferred method is EchoMTG's card reference call, accessible here via the `cardReference()` method. This reference is important because Promo cards **do not** have a Multiverse ID but they are assigned them by EchoMTG in this reference, allowing you to add promo cards programmatically. This method takes a string for the card name and, optionally, set code to search and returns the ID:

        $echomtg->cardReference( 'Verdant Force', 'tmp' );

2. Alternatively you can get Multiverse ID's by hand by searching for the card/printing at Gatherer or using the [MTGJson](http://mtgjson.com) API (or similar services).

The other parameters are quantity, your purchase price, the date of your purchase in mm-dd-yyyy format, and whether the card is foil (1) or not (0).

#### Removing cards from the inventory

To remove a card by the inventory ID, use `removeCard()`. It only takes the EchoMTG inventory ID ("EID") of the card in your inventory.

#### Adjusting the acquisition price

To adjust the acquisition price of a card in inventory, call `adjustAcquiredPrice()`, passing in the card's EID in your inventory and the price you want to set.

#### Toggling foil status

Set a card in inventory as a foil (1) or not foil (0) by calling `toggleFoil()`, passing in the card's EID and the foil boolean.

#### Adjusting the acquisition date

To adjust the acquisition date of a card in inventory, call `adjustAcquiredDate()`, passing in the card's EID and the date you want to set in `MM-DD-YYYY` format (`m-d-Y` in PHP `date()` lingo).

### Viewing Inventory

#### Getting the inventory

Use the `getInventory()` method to return the user's inventory. By default the method will return the most recently acquired 10 cards.

You can pass in parameters for start and end values to limit the results, the attribute to **sort** on (`price, cmc, foil_price, date_aquired, set`), sort **order** (`desc, asc`), a **card name** to search, **color** (`Colorless, Multicolor, White, Blue, Black, Red, Green, Land`), **card type** (`Planeswalker, Sorcery, Instant, Creature, Artifact, Enchantment, Legendary, Land`), and **set code**.

E.g., the following returns the most recent 10 cards that are green legends, sorted by price descending:

    $echomtg->getInventory( 0, 9, 'price', 'desc', null, 'Green', 'Legendary' );

#### Getting inventory statistics

Call the `getStats()` method to return the user's inventory stats. This takes no parameters and sends an authenticated GET.

## Debugging

To enable debugging mode, set the class property `$debug_mode` to true in `api.php`. This adds all auth, session, and requests/responses to the `$debug` property. Output that to view the current debugging log:

    print_r( $echomtg->debug );
