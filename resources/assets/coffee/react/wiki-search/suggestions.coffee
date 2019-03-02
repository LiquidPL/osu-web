###
#    Copyright 2015-2018 ppy Pty. Ltd.
#
#    This file is part of osu!web. osu!web is distributed with the hope of
#    attracting more community contributions to the core ecosystem of osu!.
#
#    osu!web is free software: you can redistribute it and/or modify
#    it under the terms of the Affero GNU General Public License version 3
#    as published by the Free Software Foundation.
#
#    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
#    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#    See the GNU Affero General Public License for more details.
#
#    You should have received a copy of the GNU Affero General Public License
#    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
###
{div, span} = ReactDOMFactories
el = React.createElement

class @WikiSearch.Suggestions extends React.Component
  render: ->
    div
      className: osu.classWithModifiers('wiki-search-suggestions', ['visible' if @props.visible]),
      if @props.loading
        div
          className: 'wiki-search-suggestions__spinner'
          el Spinner

      for suggestion, i in @props.suggestions
        el WikiSearch.Suggestion,
          key: i
          position: i
          suggestion: suggestion
          highlighted: i == @props.highlighted

      if @props.highlighted == null
        div
          className: 'wiki-search-suggestions__prompt',
          osu.trans 'wiki.main.search-enter-prompt'