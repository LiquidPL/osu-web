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
@extends("master")

@section("content")
@include("store.header")

<div class="row-page row-blank store-index">
    @foreach($products as $p)
        <div
                class="store-index__item {{ $p->promoted ? "store-index__item--wide" : "" }}"
                style="background-image: url('{{ $p->promoted ? $p->header_image : $p->image }}')">
            <a class="store-index__item-content" href="/store/product/{{{$p->product_id}}}">
                <div class="store-index__item-description {{ $p->promoted ? "store-index__item-description--wide" : "" }}">
                    {!! Markdown::convertToHtml($p->header_description) !!}
                </div>

                @if (! $p->inStock())
                    <i class="store-index__item-bar store-index__item-bar--oos"></i>
                @endif
            </a>
        </div>
    @endforeach

    <div class="store-index__item" style="background-image: url(//puu.sh/8Bj8T/d6009fc9ee.png)">
        <div class="store-index__item-description store-index__item-description--more">
            <h1>More to come!</h1>
            <p class="always-visible">We're just getting started... <strong>check back soon!</strong></p>
        </div>
    </div>
</div>
@stop
