{{--
    Copyright 2015 ppy Pty. Ltd.

    This file is part of osu!web. osu!web is distributed with the hope of
    attracting more community contributions to the core ecosystem of osu!.

    osu!web is free software: you can redistribute it and/or modify
    it under the terms of the Affero GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
--}}
<div class="row-page header-row no-print store-header">
    @if ((new Carbon\Carbon("2015-07-15"))->isFuture())
        <a href="{{ action("StoreController@getListing") }}" class="store-header__logo store-header__logo--late"></a>
    @else
        <a href="{{ action("StoreController@getListing") }}" class="store-header__logo store-header__logo--svg">
            @include("store._logo")
        </a>
    @endif

    @if(!isset($skip_back_link))
        <div class="store-header__float">
            <a href="javascript:history.back()" class="store-header__float-link">
                <i class="fa fa-chevron-left store-header__float-icon store-header__float-icon--left"></i>
                back
            </a>
        </div>
    @endif

    @if(isset($cart) && $cart && $cart->items()->exists())
        <div class="store-header__float store-header__float--right">
            <a href="/store/cart" class="store-header__float-link">
                {{ $cart->getItemCount() }} item(s) in cart (${{ $cart->getSubtotal() }})
                <i class="fa fa-shopping-cart store-header__float-icon store-header__float-icon--right"></i>
            </a>
        </div>
    @endif
</div>
