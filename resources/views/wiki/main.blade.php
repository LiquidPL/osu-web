{{--
    Copyright 2015-2018 ppy Pty. Ltd.

    This file is part of osu!web. osu!web is distributed with the hope of
    attracting more community contributions to the core ecosystem of osu!.

    osu!web is free software: you can redistribute it and/or modify
    it under the terms of the Affero GNU General Public License version 3
    as published by the Free Software Foundation.

    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
--}}

@extends('master', [
    'bodyAdditionalClasses' => 'osu-layout--body-333',
])

@section('content')
    <div class="osu-layout osu-layout__row">
        <div class="osu-page-header osu-page-header--wiki-main-page">
            <div class="osu-page-header__title-box">
                <span class="osu-page-header__title osu-page-header__title--icon">
                    <i class="fa fa-university"></i>
                </span>
                <h1 class="osu-page-header__title osu-page-header__title--main">{{ trans('wiki.main.title') }}</h1>
                <h2 class="osu-page-header__title osu-page-header__title--small">{{ trans('wiki.main.subtitle') }}</h2>
            </div>
        </div>
    </div>
    <div class="osu-page osu-page--wiki wiki-main-page">
        @include('wiki._notice')
        <div class="js-react--wiki-search"></div>
        <div class="wiki-main-page__blurb">
            {!! trans('wiki.main.blurb') !!}
        </div>
        <div class="wiki-main-page__panels">
            {!! $page->page()['output'] !!}
        </div>
    </div>

    @if(Auth::user() !== null)
        @include('layout._extra_js', ['src' => 'js/react/wiki-search.js'])
    @endif
@endsection
