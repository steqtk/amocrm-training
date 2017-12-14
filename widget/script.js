define(['jquery', 'lib/components/base/modal'], function ($, Modal) {
  var CustomWidget = function () {
    var self = this,
      system = self.system;
 
    self.openModal = function (data) {
        // Modal
          modal = new Modal({
          class_name: 'modal-window',
           init: function ($modal_body) {
           var $this = $(this);
           $modal_body
              .trigger('modal:loaded') //запускает отображение модального окна
              .html(data)
              .trigger('modal:centrify')  //настраивает модальное окно
              .append('<span class="modal-body__close"><span class="icon icon-modal-close"></span></span>');
             },
            destroy: function () {}
         });
    }

      self.getTemplate = function (template, params, callback) {
            params = (typeof params == 'object')?params:{};
            template = template || '';
 
            return self.render({
                href:'/templates/' + template + '.twig',
                base_path:self.params.path, //тут обращение к объекту виджет вернет /widgets/#WIDGET_NAME#
                load: callback //вызов функции обратного вызова
            }, params); //параметры для шаблона
        }

     this.callbacks = {
      settings: function () {
        return true;
      },
      dpSettings: function () {
      },
      init: function () {
        return true;
      },
      bind_actions: function () {
        return true;
      },
      render: function () {
        self.getTemplate('button', {}, function(data){
            $('#nano-card-widgets').html(data.render());
        });

        $(document).off('click', '#popUPid');
        $(document).on('click', '#popUPid', function(event) {
            event.stopPropagation();
            event.preventDefault();
            self.openModal("<div>Сайт рыбатекст поможет дизайнеру, верстальщику, вебмастеру сгенерировать несколько абзацев более менее осмысленного текста рыбы на русском языке, а начинающему оратору отточить навык публичных выступлений в домашних условиях. При создании генератора мы использовали небезизвестный универсальный код речей. Текст генерируется абзацами случайным образом от двух до десяти предложений в абзаце, что позволяет сделать текст более привлекательным и живым для визуально-слухового восприятия.</div>")
        });
        return true;
      },
      contacts: {
      },
      leads: {
      },
      onSave: function () { 
        return true;
      }
    };
    return this;
  };
  return CustomWidget;
});
