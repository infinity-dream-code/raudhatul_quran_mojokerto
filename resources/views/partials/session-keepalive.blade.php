<script>
(function () {
    var keepAliveUrl = @json(route('session.keep-alive'));
    var busy = false;
    var intervalMs = 4 * 60 * 1000;

    function applyCsrf(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="_token"]').forEach(function (el) {
            el.value = token;
        });
        if (window.jQuery) {
            window.jQuery.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': token }
            });
        }
    }

    function refreshCsrf() {
        return fetch(keepAliveUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (res) {
            if (!res.ok) throw new Error('keepalive failed');
            return res.json();
        }).then(function (data) {
            applyCsrf(data && data.csrf_token);
            return data && data.csrf_token;
        });
    }

    function ping() {
        if (busy || document.hidden) return;
        busy = true;
        refreshCsrf().catch(function () {}).finally(function () {
            busy = false;
        });
    }

    setInterval(ping, intervalMs);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) ping();
    });

    function withUpdatedToken(options, token) {
        var opts = window.jQuery.extend(true, {}, options);
        opts._csrfRetried = true;
        opts.headers = opts.headers || {};
        opts.headers['X-CSRF-TOKEN'] = token;
        if (typeof opts.data === 'string' && opts.data.indexOf('_token=') !== -1) {
            opts.data = opts.data.replace(/_token=[^&]*/, '_token=' + encodeURIComponent(token));
        } else if (opts.data && typeof opts.data === 'object' && !(window.FormData && opts.data instanceof FormData)) {
            if (Object.prototype.hasOwnProperty.call(opts.data, '_token')) {
                opts.data._token = token;
            }
        } else if (window.FormData && opts.data instanceof FormData) {
            opts.data.set('_token', token);
        }
        return opts;
    }

    if (window.jQuery) {
        var $ = window.jQuery;
        var originalAjax = $.ajax;
        $.ajax = function (url, options) {
            if (typeof url === 'object') {
                options = url;
                url = undefined;
            }

            var raw = $.extend(true, {}, options || {});
            var userError = raw.error;
            var userComplete = raw.complete;
            var userStatusCode = raw.statusCode;
            delete raw.error;
            delete raw.complete;
            if (raw.statusCode) {
                delete raw.statusCode[419];
            }

            var deferred = $.Deferred();
            var first = originalAjax.call($, url, raw);

            first.done(function () {
                if (userComplete) userComplete.apply(this, arguments);
                deferred.resolveWith(this, arguments);
            }).fail(function (xhr) {
                var args = arguments;
                var ctx = this;

                if (!xhr || xhr.status !== 419 || raw._csrfRetried) {
                    if (userError) userError.apply(ctx, args);
                    if (userStatusCode && userStatusCode[xhr && xhr.status]) {
                        userStatusCode[xhr.status].apply(ctx, args);
                    }
                    if (userComplete) userComplete.apply(ctx, args);
                    deferred.rejectWith(ctx, args);
                    return;
                }

                refreshCsrf().then(function (token) {
                    var retryOpts = withUpdatedToken(raw, token);
                    retryOpts.error = userError;
                    retryOpts.complete = userComplete;
                    retryOpts.statusCode = userStatusCode;
                    return originalAjax.call($, url, retryOpts);
                }).done(function () {
                    deferred.resolveWith(this, arguments);
                }).fail(function () {
                    deferred.rejectWith(this, arguments);
                });
            });

            return deferred.promise(first);
        };
    }

    if (window.fetch) {
        var rawFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            init = init || {};
            return rawFetch(input, init).then(function (res) {
                if (res.status !== 419 || init._csrfRetried) return res;
                return refreshCsrf().then(function (token) {
                    var nextInit = Object.assign({}, init, { _csrfRetried: true });
                    var headers = new Headers(init.headers || {});
                    headers.set('X-CSRF-TOKEN', token);
                    headers.set('X-XSRF-TOKEN', token);
                    nextInit.headers = headers;
                    if (typeof nextInit.body === 'string' && nextInit.body.indexOf('_token=') !== -1) {
                        nextInit.body = nextInit.body.replace(/_token=[^&]*/, '_token=' + encodeURIComponent(token));
                    }
                    return rawFetch(input, nextInit);
                }).catch(function () { return res; });
            });
        };
    }

    window.__refreshCsrf = refreshCsrf;
})();
</script>
