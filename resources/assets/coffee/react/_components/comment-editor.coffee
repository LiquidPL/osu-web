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

{button, div, span} = ReactDOMFactories

el = React.createElement

bn = 'comment-editor'

class @CommentEditor extends React.PureComponent
  constructor: (props) ->
    super props

    @textarea = null

    @state =
      message: @props.message ? ''
      posting: false


  componentDidMount: =>
    @textarea.selectionStart = -1
    @textarea.focus() if (@props.focus ? true)


  componentWillUnmount: =>
    @xhr?.abort()


  render: =>
    blockClass = osu.classWithModifiers bn, @props.modifiers
    blockClass += " #{bn}--compact" if @mode() == 'new'

    div className: blockClass,
      el TextareaAutosize,
        className: "#{bn}__message"
        innerRef: @setTextarea
        value: @state.message
        placeholder: osu.trans("comments.placeholder.#{@mode()}")
        onChange: @onChange
        disabled: !currentUser.id? || @state.posting
      div
        className: "#{bn}__footer"
        if @props.close?
          div className: "#{bn}__footer-item",
            button
              className: 'btn-osu-big btn-osu-big--comment-editor'
              onClick: @props.close
              disabled: @state.posting
              osu.trans('common.buttons.cancel')

        if currentUser.id?
          div className: "#{bn}__footer-item",
            button
              className: 'btn-osu-big btn-osu-big--comment-editor'
              onClick: @post
              disabled: @state.posting || !@isValid()
              span className: 'btn-osu-big__content',
                @buttonText()
        else
          div className: "#{bn}__footer-item",
            button
              className: 'btn-osu-big btn-osu-big--comment-editor js-user-link'
              span className: 'btn-osu-big__content',
                osu.trans("comments.guest_button.#{@mode()}")


  buttonText: =>
    key =
      switch @mode()
        when 'reply' then 'reply'
        when 'edit' then 'save'
        when 'new' then 'post'

    osu.trans("common.buttons.#{key}")


  isValid: =>
    @state.message? && @state.message.length > 0


  mode: =>
    if @props.parent?
      'reply'
    else if @props.id?
      'edit'
    else
      'new'


  onChange: (e) =>
    @setState message: e.target.value


  post: =>
    return @props.close?() if @mode() == 'edit' && @state.message == @props.message

    @setState posting: true

    data = comment: message: @state.message

    switch @mode()
      when 'reply', 'new'
        url = laroute.route 'comments.store'
        method = 'POST'
        data.comment.commentable_type = @props.commentableType
        data.comment.commentable_id = @props.commentableId
        data.comment.parent_id = @props.parent?.id

        onDone = (data) =>
          @setState message: ''
          $.publish 'comments:added', comments: data
      when 'edit'
        url = laroute.route 'comments.update', comment: @props.id
        method = 'PUT'

        onDone = (data) ->
          $.publish 'comment:updated', comment: data

    @xhr = $.ajax url, {method, data}
    .always =>
      @setState posting: false
    .done (data) =>
      onDone(data)
      @props.close?()
    .fail (xhr, status) =>
      osu.ajaxError(xhr, status)


  setTextarea: (ref) =>
    @textarea = ref
