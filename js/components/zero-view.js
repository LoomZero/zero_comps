(function($) {
  'use strict';

  Drupal.zero.createComponent('zero-view', {

    itemTemplate: null,
    items: null,
    page: 0,

    /**
     * @param {string} name
     * @param {boolean} value
     * @return {boolean}
     */
    state: function state(name, value) {
      if (typeof value === 'boolean') {
        const settings = this.settings();
        if (value) {
          this.item.addClass(this.component + '--' + name);
          if (settings.design) this.item.addClass(settings.design + '--' + name);
        } else {
          this.item.removeClass(this.component + '--' + name);
          if (settings.design) this.item.removeClass(settings.design + '--' + name);
        }
      }
      return this.item.hasClass(this.component + '--' + name);
    },

    debounce: function(func, wait) {
      const that = this;
      let timeout = null;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
          timeout = null;
          func.apply(that, args);
        }, wait);
      };
    },

    init: function() {
      const settings = this.settings();
      this.itemTemplate = this.createTemplate('item-template');
      this.items = this.element('items');
      this.page = settings.startPage;
      this.ignoreAction = false;

      this.filters = {};
      this.element('filters').find('[data-filter-key]').each((i, element) => {
        const filter = $(element);
        const trigger = filter.data('filter-trigger');

        this.filters[filter.data('filter-key')] = filter;
        if (trigger.startsWith('delay')) {
          filter.on('keyup', this.debounce(e => {
            e.preventDefault();
            if (!this.ignoreAction) this.update(this.getFilters(), 0, true);
          }, parseInt(trigger.split(':')[1] || 300)));
        } else {
          filter.on(trigger, e => {
            e.preventDefault();
            if (!this.ignoreAction) this.update(this.getFilters(), 0, true);
          });
        }
      });

      this.element('more-trigger').on('click', e => {
        e.preventDefault();
        this.loadNext();
      });

      console.log(this, settings);
    },

    loadNext: function() {
      this.update(this.getFilters(), this.page + 1);
    },

    update: function(filters, page, reset = false) {
      if (this.state('loading')) return;

      this.state('loading', true);
      if (reset) {
        this.items.html('');
      }
      const settings = this.settings();
      const request = JSON.parse(JSON.stringify(settings.request));

      request.page = page;
      request.filters = filters;
      this.request(request._id, request, request._format, this.onResponse.bind(this), this.onError.bind(this));
    },

    onResponse: function(ajax, data, meta) {
      if (!this.checkResponse(data, meta)) {
        this.state('loading', false);
        this.state('error', true);
        return;
      }
      this.state('no-more', meta.view.remain <= 0);
      this.page = meta.page;
      for (const index in data.items) {
        this.items.append(this.itemTemplate(data.items[index]));
      }
      this.state('loading', false);
    },

    onError: function() {
      this.state('loading', false);
      this.state('error', true);
      console.log('error', arguments);
    },

    checkResponse: function(data, meta) {
      if (!meta || typeof meta.page !== 'number') {
        console.error('ERROR: No "page" in meta data. Please insert "page" as meta in ajax plugin with "$request->setMeta(\'page\', $view->getCurrentPage());".');
        return false;
      }
      if (!meta || !meta.view || typeof meta.view.remain !== 'number') {
        console.error('ERROR: No "view.remain" in meta data. Please insert "view.remain" as meta in ajax plugin with "$request->setMeta(\'view\', $view->getResultMeta());".');
        return false;
      }
      return true;
    },

    setFilters: function(filters, reactive = true) {
      if (reactive) {
        this.ignoreAction = true;
        for (const key in this.filters) {
          this.filters[key].val(filters[key] || '');
        }
        this.ignoreAction = false;
      }
      this.update(filters, 0, true);
    },

    getFilters: function() {
      const filters = {};

      for (const key in this.filters) {
        filters[key] = this.filters[key].val();
      }
      return filters;
    },

  });

})(jQuery);
