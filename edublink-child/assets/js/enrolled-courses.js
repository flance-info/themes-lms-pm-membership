"use strict";

(function ($) {
  $(document).ready(function () {
    new Vue({
      el: '#enrolled-courses',
      data: function data() {
        return {
          vue_loaded: true,
          loading: false,
          loadingButton: false,
          statsVisible: true,
          stats: [],
          courses: [],
          total: true,
          offsets: {
            all: 0,
            completed: 0,
            failed: 0,
            in_progress: 0
          },
          activeTab: 'all'
        };
      },
      mounted: function mounted() {
        this.cookieCheck();
        this.getStudentStats();
        this.getCourses('all');
      },
      methods: {
        getStudentStats: function getStudentStats() {
          var vm = this;
          var apiUrl = "".concat(ms_lms_resturl, "/student/stats/").concat(student_data.id);
          fetch(apiUrl, {
            method: 'GET',
            headers: {
              'X-WP-Nonce': ms_lms_nonce
            }
          }).then(function (response) {
            if (response.ok) {
              return response.json();
            }
          }).then(function (data) {
            vm.stats = data;
          })["catch"](function (error) {
            console.error('There was a problem with the fetch operation:', error);
          });
        },
        getCourses: function getCourses(status) {
          var more = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
          var withLoading = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
          var vm = this;
          if (more) {
            vm.offsets[status] += 1;
          } else {
            vm.offsets[status] = 0;
          }
          var currentOffset = vm.offsets[status];
          var url = stm_lms_ajaxurl + '?action=stm_lms_get_user_courses&offset=' + currentOffset + '&nonce=' + stm_lms_nonces['stm_lms_get_user_courses'] + '&status=' + status;
          vm.activeTab = status;
          if (withLoading) {
            vm.loadingButton = true;
          } else {
            vm.loading = true;
            vm.courses = [];
          }
          this.$http.get(url).then(function (response) {
            if (response.body['posts']) {
              if (currentOffset === 0) {
                vm.courses = response.body['posts'];
              } else {
                response.body['posts'].forEach(function (course) {
                  vm.courses.push(course);
                });
              }
            }
            vm.total = response.body['total'];
            vm.loading = false;
            vm.loadingButton = false;
            Vue.nextTick(function () {
              $('a.coming-soon-not-allowed').each(function () {
                $(this).removeAttr('href');
              });
              stmLmsStartTimers();
            });
          });
        },
        cookieCheck: function cookieCheck() {
          var hideStats = this.getCookie('hideStats');
          if (hideStats === 'true') {
            this.statsVisible = !this.statsVisible;
          }
        },
        getCookie: function getCookie(name) {
          var _parts$pop;
          var value = "; ".concat(document.cookie);
          var parts = value.split("; ".concat(name, "="));
          if (parts.length === 2) return (_parts$pop = parts.pop()) === null || _parts$pop === void 0 ? void 0 : _parts$pop.split(';').shift();
        },
        setCookie: function setCookie(name, value) {
          var date = new Date();
          date.setFullYear(date.getFullYear() + 10);
          document.cookie = "".concat(name, "=").concat(value, ";expires=").concat(date.toUTCString(), ";path=/");
        },
        showStats: function showStats() {
          this.statsVisible = !this.statsVisible;
          this.setCookie('hideStats', !this.statsVisible ? 'true' : 'false');
        }
      }
    });
  });
})(jQuery);