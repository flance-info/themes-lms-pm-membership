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
          activeTab: 'all',
          filters: {},
          errorMessage: null
        };
      },
      mounted: function mounted() {
        this.cookieCheck();
        this.getStudentStats();
        this.getCourses('all');
        this.initFilters();
        this.errorMessageContainer = document.getElementById('error-message-container');
      },
      methods: {
        initFilters: function() {
          var vm = this;
          $('#stm_filter_form').on('change', 'input, select', function() {
            vm.updateFilters();
            vm.getCourses(vm.activeTab);
          });

          $('#stm_filter_form').on('click', '.stm_lms_courses__filter_reset', function(e) {
            e.preventDefault();
            vm.filters = {};
            vm.getCourses(vm.activeTab);
          });
        },
        updateFilters: function() {
          var formData = $('#stm_filter_form').serializeArray();
          this.filters = {};
          formData.forEach(function(item) {
            if (item.value) {
              var filterKey = 'filter_' + item.name;
              
              if (this.filters[filterKey]) {
                if (Array.isArray(this.filters[filterKey])) {
                  this.filters[filterKey].push(item.value);
                } else {
                  this.filters[filterKey] = [this.filters[filterKey], item.value];
                }
              } else {
                this.filters[filterKey] = item.value;
              }
            }
          }.bind(this));
        },
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
          
          if (Object.keys(vm.filters).length > 0) {
            for (var key in vm.filters) {
              if (vm.filters.hasOwnProperty(key)) {
                if (Array.isArray(vm.filters[key])) {
                  vm.filters[key].forEach(function(value) {
                    url += '&' + key + '[]=' + encodeURIComponent(value);
                  });
                } else {
                  url += '&' + key + '=' + encodeURIComponent(vm.filters[key]);
                }
              }
            }
          }
          
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
        },
        unlockCourse(courseId) {
            var vm = this;

            // Show loader
            vm.loading = true;

            // Make AJAX request to stm_lms_use_membership using Vue's $http
            vm.$http.get(stm_lms_ajaxurl, {
                params: {
                    action: 'stm_lms_use_membership',
                    course_id: courseId,
                    nonce: stm_lms_nonces['stm_lms_use_membership'] // Assuming you have a nonce available
                }
            })
            .then(response => {
                if (response.body && response.body.error) {
                    // Handle error response
                    console.log('Error unlocking course:', response.body.error);
                    vm.loading = false;
                    vm.errorMessage = response.body.error;
                } else {
                    // Handle successful response
                    console.log('Course unlocked successfully:', response.body);
                    
                    // Show success message
                    vm.showMessage('success', 'Course unlocked successfully!');

                    // Reload the page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500); // 1.5 seconds delay
                }
            })
            .catch(error => {
                // Handle error
                console.error('Error unlocking course:', error);
                vm.loading = false;
                // Set error message
                vm.errorMessage = 'Error unlocking course. Please try again.';
            })
            .finally(() => {
                // Hide loader
              
            });
        },
        showMessage: function showMessage(type, message) {
            // Implement the logic to show a message to the user
            console.log(type + ': ' + message);
        }
      },
      watch: {
        errorMessage(newValue) {
            if (newValue) {
                this.errorMessageContainer.innerHTML = `<div class="error-message">${newValue}</div>`;
            } else {
                this.errorMessageContainer.innerHTML = '';
            }
        },
        loading(newValue) {
            if (newValue === true) {
                this.errorMessage = null;
            }
        }
      }
    });
  });
})(jQuery);