function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

(function (_, $) {
  var createMoment = function createMoment(input) {
    // Unix timestamp
    if (isFinite(input)) {
      return moment.unix(input);
    } // ISO 8601
    else {
        return moment(input, moment.ISO_8601);
      }
  };

  var methods = {
    init: function init(params) {
      var _ranges, _periods;

      var $dateRangePickers = $(this);

      if (typeof moment === 'undefined') {
        $.loadCss(['js/lib/daterangepicker/daterangepicker.css']);
        $.getScript('js/lib/daterangepicker/moment.min.js', function () {
          $.getScript('js/lib/daterangepicker/daterangepicker.js', function () {
            return $dateRangePickers.ceDateRangePicker();
          });
        });
        return false;
      }

      if (!$dateRangePickers.length) {
        return;
      }

      moment.updateLocale(_.tr("default_lang"), {
        monthsShort: [_.tr("month_name_abr_1"), _.tr("month_name_abr_2"), _.tr("month_name_abr_3"), _.tr("month_name_abr_4"), _.tr("month_name_abr_5"), _.tr("month_name_abr_6"), _.tr("month_name_abr_7"), _.tr("month_name_abr_8"), _.tr("month_name_abr_9"), _.tr("month_name_abr_10"), _.tr("month_name_abr_11"), _.tr("month_name_abr_12")]
      });
      moment.locale(_.tr("default_lang"));
      var default_params = {
        ranges: (_ranges = {}, _defineProperty(_ranges, _.tr('today'), [moment().startOf('day'), moment().endOf('day')]), _defineProperty(_ranges, _.tr('yesterday'), [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')]), _defineProperty(_ranges, _.tr('this_month'), [moment().startOf('month'), moment().endOf('month')]), _defineProperty(_ranges, _.tr('last_month'), [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]), _defineProperty(_ranges, _.tr('this_year'), [moment().startOf('year').startOf('day'), moment().endOf('year').endOf('day')]), _defineProperty(_ranges, _.tr('last_year'), [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]), _ranges),
        locale: {
          applyLabel: _.tr("apply"),
          cancelLabel: _.tr("cancel"),
          clearLabel: _.tr("clear"),
          fromLabel: _.tr("from"),
          toLabel: _.tr("to"),
          customRangeLabel: _.tr("custom_range"),
          monthNames: [_.tr("month_name_abr_1"), _.tr("month_name_abr_2"), _.tr("month_name_abr_3"), _.tr("month_name_abr_4"), _.tr("month_name_abr_5"), _.tr("month_name_abr_6"), _.tr("month_name_abr_7"), _.tr("month_name_abr_8"), _.tr("month_name_abr_9"), _.tr("month_name_abr_10"), _.tr("month_name_abr_11"), _.tr("month_name_abr_12")],
          daysOfWeek: [_.tr("weekday_abr_0"), _.tr("weekday_abr_1"), _.tr("weekday_abr_2"), _.tr("weekday_abr_3"), _.tr("weekday_abr_4"), _.tr("weekday_abr_5"), _.tr("weekday_abr_6")]
        },
        showDropdowns: true,
        autoApply: true
      }; // but, if we had .admin-content and RTL enabled, place picker in this wrapper

      if ($('.admin-content').length && Tygh.language_direction === 'rtl') {
        default_params.parentEl = '.admin-content';
      }

      if (_.daterangepicker.customRangeFormat) {
        default_params.format = _.daterangepicker.customRangeFormat;
        default_params.locale.format = _.daterangepicker.customRangeFormat;
      }

      if (_.time_from) {
        default_params.startDate = createMoment(_.time_from);
      }

      if (_.time_to) {
        default_params.endDate = createMoment(_.time_to);
      }

      if (_.daterangepicker.minDate) {
        default_params.minDate = createMoment(_.daterangepicker.minDate);
      }

      if (_.daterangepicker.maxDate) {
        default_params.maxDate = createMoment(_.daterangepicker.maxDate);
      }

      var periods = (_periods = {}, _defineProperty(_periods, _.tr('today'), 'D'), _defineProperty(_periods, _.tr('yesterday'), 'LD'), _defineProperty(_periods, _.tr('this_month'), 'M'), _defineProperty(_periods, _.tr('last_month'), 'LM'), _defineProperty(_periods, _.tr('this_year'), 'Y'), _defineProperty(_periods, _.tr('last_year'), 'LY'), _periods);
      return $dateRangePickers.each(function () {
        var $self = $(this);

        if ($self.data('daterangepicker')) {
          return;
        }

        var element_params = $.extend(true, {}, default_params);

        if (!$self.data('caShowRanges')) {
          delete element_params.ranges;
        }

        if ($self.data('caTimeFrom')) {
          element_params.startDate = createMoment($self.data('caTimeFrom'));
        }

        if ($self.data('caTimeTo')) {
          element_params.endDate = createMoment($self.data('caTimeTo'));
        }

        if ($self.data('caDateFormat')) {
          element_params.format = $self.data('caDateFormat');
          element_params.locale = {
            format: $self.data('caDateFormat')
          };
        }

        if ($self.data('caMinDate')) {
          element_params.minDate = createMoment($self.data('caMinDate'));
        }

        if ($self.data('caMaxDate')) {
          element_params.maxDate = createMoment($self.data('caMaxDate'));
        }

        if ($self.data('caUnavailableDates')) {
          element_params.isInvalidDate = function (day) {
            return $(this)[0].element.data('caUnavailableDates').some(function (unavailableDate) {
              return unavailableDate === day.format('YYYY-MM-DD');
            });
          };

          element_params.isCustomDate = function (day) {
            return $(this)[0].element.data('caUnavailableDates').some(function (unavailableDate) {
              return unavailableDate === day.format('YYYY-MM-DD');
            }) ? 'ty-date-range__unavailable-day' : '';
          };
        }

        var settings = $.extend(true, {}, element_params, params);
        $self.daterangepicker(settings, function (start, end, label) {
          var query_params;
          start = moment(start).local().startOf('day');
          end = moment(end).local().endOf('day');
          var selected_from = parseInt(start.valueOf() / 1000, 10);
          var selected_to = parseInt(end.valueOf() / 1000, 10);

          if (($self.data('caUsePredefinedPeriods') || _.daterangepicker.usePredefinedPeriods) && periods[label] != undefined) {
            query_params = 'time_period=' + periods[label];
          } else {
            query_params = 'time_from=' + selected_from + '&time_to=' + selected_to;
          }

          $('.cm-date-range__selected-date', $self).html(start.format($self.data('caDisplayedFormat') || _.daterangepicker.displayedFormat) + ' — ' + end.format($self.data('caDisplayedFormat') || _.daterangepicker.displayedFormat));

          if ($self.data('ca-target-url') && $self.data('ca-target-id')) {
            $.ceAjax('request', $.attachToUrl($self.data('ca-target-url'), query_params), {
              result_ids: $self.data('ca-target-id'),
              caching: false,
              force_exec: true
            });
          }

          methods.updateInputs($self, selected_from, selected_to);
          methods.updateTimeCount($self, selected_from, selected_to);

          if ($self.data('caEvent')) {
            $.ceEvent('trigger', $self.data('caEvent'), [$self, selected_from, selected_to, start, end]);
          }
        });
      });
    },
    updateInputs: function updateInputs($el, selected_from, selected_to) {
      var $dateRange = $el.closest('.cm-date-range');
      var selected_from_formated = createMoment(selected_from).format($el.data('caDateFormat') || _.daterangepicker.customRangeFormat);
      var selected_to_formated = createMoment(selected_to).format($el.data('caDateFormat') || _.daterangepicker.customRangeFormat);
      $('[data-ca-date-range-picker="date-in"]', $dateRange).val(selected_from_formated);
      $('[data-ca-date-range-picker="date-out"]', $dateRange).val(selected_to_formated);
    },
    updateTimeCount: function updateTimeCount($el, selected_from, selected_to) {
      var startDay = moment.unix(selected_from);
      var endDay = moment.unix(selected_to).startOf('day');
      var timeCount = moment.duration(endDay.diff(startDay));
      $el.data('caTimeCount', timeCount.asSeconds());
    }
  };

  $.fn.ceDateRangePicker = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error('ty.tooltip: method ' + method + ' does not exist');
    }
  };

  $.ceEvent('on', 'ce.commoninit', function (context) {
    $dateRange = $('.cm-date-range', context);

    if (!$dateRange.length) {
      return;
    }

    $dateRange.ceDateRangePicker();
  });
})(Tygh, Tygh.$);