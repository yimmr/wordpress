var imwp = imwp || {}

// 触发自定义事件
imwp.emit = function (el, name) {
    var e = document.createEvent('Event')
    e.initEvent(name)
    el.dispatchEvent(e)
}

imwp.formImage = {
    // 父节点触发已上传事件
    upload: function (el) {
        if (typeof el.media === 'undefined') {
            el.media = wp.media({ library: { type: 'image' }, multiple: false })
            el.parentNode.media = el.media
            el.media.on('select', function () {
                var image = el.media.state().get('selection').first().attributes
                var input = el.parentNode.querySelector('input')
                var img = el.parentNode.querySelector('img')
                if (!img) {
                    img = document.createElement('img')
                    el.parentNode.insertBefore(img, el.parentNode.firstChild)
                }
                img.setAttribute('src', image.url)
                if (input) input.setAttribute('value', image.id)
                imwp.emit(el.parentNode, 'uploaded')
            })
        }
        el.media.open()
    },
    // 父节点触发取消事件
    cancel: function (el) {
        var input = el.parentNode.querySelector('input')
        var img = el.parentNode.querySelector('img')
        if (img) el.parentNode.removeChild(img)
        if (input) input.setAttribute('value', '')
        imwp.emit(el.parentNode, 'cancel')
    },
}

imwp.FormImageGroup = function (el) {
    this.el = el
    this.count = el.getAttribute('data-count') || 1
    this.defField = el.querySelector('.image-field.empty')
    this.defField.remove()
    this.defField.style.display = ''
    var fields = this.el.querySelectorAll('.image-field')
    for (var i = 0; i < fields.length; i++) {
        this.addEvents(fields[i])
    }
    this.checkLen(fields)
}

imwp.FormImageGroup.prototype.checkLen = function (fields) {
    fields = fields || this.el.querySelectorAll('.image-field')
    if (fields.length < this.count && !this.el.querySelector('.image-field.empty')) {
        var field = this.defField.cloneNode(true)
        this.addEvents(field)
        this.el.appendChild(field)
    }
}

imwp.FormImageGroup.prototype.addEvents = function (field) {
    var self = this
    field.addEventListener('uploaded', function () {
        this.classList.remove('empty')
        self.checkLen()
    })
    field.addEventListener('cancel', function () {
        this.remove()
        self.checkLen()
    })
}

document.addEventListener('DOMContentLoaded', function () {
    var groups = document.querySelectorAll('.image-field-group')
    for (var i = 0; i < groups.length; i++) {
        new imwp.FormImageGroup(groups[i])
    }
})
