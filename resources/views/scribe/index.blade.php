<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Connettore WooCommerce API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.10.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.10.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-ordini-woocommerce" class="tocify-header">
                <li class="tocify-item level-1" data-unique="ordini-woocommerce">
                    <a href="#ordini-woocommerce">Ordini WooCommerce</a>
                </li>
                                    <ul id="tocify-subheader-ordini-woocommerce" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="ordini-woocommerce-GETapi-v1-woocommerce--store--orders">
                                <a href="#ordini-woocommerce-GETapi-v1-woocommerce--store--orders">Lista ordini</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="ordini-woocommerce-GETapi-v1-woocommerce--store--orders--id-">
                                <a href="#ordini-woocommerce-GETapi-v1-woocommerce--store--orders--id-">Dettaglio ordine</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-stato-servizio" class="tocify-header">
                <li class="tocify-item level-1" data-unique="stato-servizio">
                    <a href="#stato-servizio">Stato servizio</a>
                </li>
                                    <ul id="tocify-subheader-stato-servizio" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="stato-servizio-GETapi-v1-health">
                                <a href="#stato-servizio-GETapi-v1-health">Health check

Verifica che il servizio sia attivo. Pubblico (nessuna IP whitelist): usato dal deploy.</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: May 29, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="ordini-woocommerce">Ordini WooCommerce</h1>

    <p>Lettura (sola lettura) degli ordini di un negozio WooCommerce via REST API v3.
Lo store è indicato nel path ({store} = slug in config/woocommerce.php).
Gli ordini sono restituiti in forma normalizzata (DTO con chiavi di dominio italiane).</p>

                                <h2 id="ordini-woocommerce-GETapi-v1-woocommerce--store--orders">Lista ordini</h2>

<p>
</p>

<p>Ritorna gli ordini dello store indicato, filtrabili e paginati. I totali di paginazione
sono esposti sia nel corpo (<code>paginazione</code>) sia negli header <code>X-WP-Total</code> / <code>X-WP-TotalPages</code>.</p>

<span id="example-requests-GETapi-v1-woocommerce--store--orders">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/woocommerce/negozio1/orders?page=1&amp;per_page=20&amp;status=completed&amp;after=2026-01-01&amp;before=2026-12-31&amp;modified_after=2026-05-01&amp;modified_before=architecto&amp;customer=7&amp;search=rossi&amp;orderby=date&amp;order=desc&amp;include=12%2C15%2C20&amp;exclude=99" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"page\": 16,
    \"per_page\": 22,
    \"status\": \"any\",
    \"after\": \"2026-05-29T12:30:15\",
    \"before\": \"2026-05-29T12:30:15\",
    \"modified_after\": \"2026-05-29T12:30:15\",
    \"modified_before\": \"2026-05-29T12:30:15\",
    \"customer\": 84,
    \"search\": \"z\",
    \"orderby\": \"slug\",
    \"order\": \"asc\",
    \"include\": \"architecto\",
    \"exclude\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/woocommerce/negozio1/orders"
);

const params = {
    "page": "1",
    "per_page": "20",
    "status": "completed",
    "after": "2026-01-01",
    "before": "2026-12-31",
    "modified_after": "2026-05-01",
    "modified_before": "architecto",
    "customer": "7",
    "search": "rossi",
    "orderby": "date",
    "order": "desc",
    "include": "12,15,20",
    "exclude": "99",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "page": 16,
    "per_page": 22,
    "status": "any",
    "after": "2026-05-29T12:30:15",
    "before": "2026-05-29T12:30:15",
    "modified_after": "2026-05-29T12:30:15",
    "modified_before": "2026-05-29T12:30:15",
    "customer": 84,
    "search": "z",
    "orderby": "slug",
    "order": "asc",
    "include": "architecto",
    "exclude": "architecto"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-woocommerce--store--orders">
            <blockquote>
            <p>Example response (200, ok):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;store&quot;: &quot;negozio1&quot;,
    &quot;dati&quot;: [
        {
            &quot;id&quot;: 123,
            &quot;numero&quot;: &quot;123&quot;,
            &quot;stato&quot;: &quot;completed&quot;,
            &quot;valuta&quot;: &quot;EUR&quot;,
            &quot;totali&quot;: {
                &quot;totale&quot;: &quot;49.90&quot;
            },
            &quot;cliente&quot;: {
                &quot;email&quot;: &quot;mario@x.it&quot;
            },
            &quot;righe&quot;: [
                {
                    &quot;prodotto&quot;: &quot;Olio EVO 1L&quot;,
                    &quot;sku&quot;: &quot;OLIO-1L&quot;,
                    &quot;quantita&quot;: 2
                }
            ]
        }
    ],
    &quot;paginazione&quot;: {
        &quot;totale&quot;: 137,
        &quot;pagine_totali&quot;: 7,
        &quot;pagina&quot;: 1,
        &quot;per_pagina&quot;: 20
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, IP non in whitelist):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;IP non autorizzato.&quot;,
    &quot;ip&quot;: &quot;203.0.113.10&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, store sconosciuto):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Store &#039;negozioX&#039; non riconosciuto.&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, filtro non valido):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Stato ordine non valido.&quot;,
    &quot;errors&quot;: {
        &quot;status&quot;: [
            &quot;Stato ordine non valido.&quot;
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (500, store non configurato):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Store WooCommerce &#039;negozio1&#039; non configurato (URL/KEY/SECRET mancanti).&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (502, upstream WooCommerce non raggiungibile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;WooCommerce[negozio1]: lista ordini fallita (HTTP 500).&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-woocommerce--store--orders" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-woocommerce--store--orders"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-woocommerce--store--orders"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-woocommerce--store--orders" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-woocommerce--store--orders">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-woocommerce--store--orders" data-method="GET"
      data-path="api/v1/woocommerce/{store}/orders"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-woocommerce--store--orders', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-woocommerce--store--orders"
                    onclick="tryItOut('GETapi-v1-woocommerce--store--orders');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-woocommerce--store--orders"
                    onclick="cancelTryOut('GETapi-v1-woocommerce--store--orders');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-woocommerce--store--orders"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/woocommerce/{store}/orders</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>store</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="store"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="negozio1"
               data-component="url">
    <br>
<p>Slug dello store WooCommerce (config/woocommerce.php). Example: <code>negozio1</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="1"
               data-component="query">
    <br>
<p>Pagina (default 1). Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="20"
               data-component="query">
    <br>
<p>Elementi per pagina (1-100, default 20). Example: <code>20</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="completed"
               data-component="query">
    <br>
<p>Stato ordine: any, pending, processing, on-hold, completed, cancelled, refunded, failed, trash. Example: <code>completed</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>after</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="after"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-01-01"
               data-component="query">
    <br>
<p>Ordini creati dopo questa data (ISO8601). Example: <code>2026-01-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>before</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="before"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-12-31"
               data-component="query">
    <br>
<p>Ordini creati prima di questa data (ISO8601). Example: <code>2026-12-31</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>modified_after</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="modified_after"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-05-01"
               data-component="query">
    <br>
<p>Ordini modificati dopo questa data (ISO8601). Example: <code>2026-05-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>modified_before</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="modified_before"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="architecto"
               data-component="query">
    <br>
<p>Ordini modificati prima di questa data (ISO8601). Example: <code>architecto</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="7"
               data-component="query">
    <br>
<p>ID cliente WooCommerce. Example: <code>7</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="rossi"
               data-component="query">
    <br>
<p>Ricerca testuale. Example: <code>rossi</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>orderby</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="orderby"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="date"
               data-component="query">
    <br>
<p>Ordinamento: date, id, title, slug, modified, include. Example: <code>date</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="desc"
               data-component="query">
    <br>
<p>Direzione: asc, desc. Example: <code>desc</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>include</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="include"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="12,15,20"
               data-component="query">
    <br>
<p>CSV di id da includere. Example: <code>12,15,20</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>exclude</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="exclude"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="99"
               data-component="query">
    <br>
<p>CSV di id da escludere. Example: <code>99</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 100. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="any"
               data-component="body">
    <br>
<p>Example: <code>any</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>any</code></li> <li><code>pending</code></li> <li><code>processing</code></li> <li><code>on-hold</code></li> <li><code>completed</code></li> <li><code>cancelled</code></li> <li><code>refunded</code></li> <li><code>failed</code></li> <li><code>trash</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>after</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="after"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-05-29T12:30:15"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-05-29T12:30:15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>before</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="before"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-05-29T12:30:15"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-05-29T12:30:15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>modified_after</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="modified_after"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-05-29T12:30:15"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-05-29T12:30:15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>modified_before</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="modified_before"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="2026-05-29T12:30:15"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-05-29T12:30:15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>customer</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="customer"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="84"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>84</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>z</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>orderby</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="orderby"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="slug"
               data-component="body">
    <br>
<p>Example: <code>slug</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>date</code></li> <li><code>id</code></li> <li><code>title</code></li> <li><code>slug</code></li> <li><code>modified</code></li> <li><code>include</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="asc"
               data-component="body">
    <br>
<p>Example: <code>asc</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>asc</code></li> <li><code>desc</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>include</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="include"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>exclude</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="exclude"                data-endpoint="GETapi-v1-woocommerce--store--orders"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="ordini-woocommerce-GETapi-v1-woocommerce--store--orders--id-">Dettaglio ordine</h2>

<p>
</p>

<p>Ritorna il singolo ordine dello store indicato, in forma normalizzata.</p>

<span id="example-requests-GETapi-v1-woocommerce--store--orders--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/woocommerce/negozio1/orders/123" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/woocommerce/negozio1/orders/123"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-woocommerce--store--orders--id-">
            <blockquote>
            <p>Example response (200, ok):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;id&quot;: 123,
    &quot;numero&quot;: &quot;123&quot;,
    &quot;stato&quot;: &quot;completed&quot;,
    &quot;valuta&quot;: &quot;EUR&quot;,
    &quot;totali&quot;: {
        &quot;totale&quot;: &quot;49.90&quot;
    },
    &quot;cliente&quot;: {
        &quot;email&quot;: &quot;mario@x.it&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, IP non in whitelist):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;IP non autorizzato.&quot;,
    &quot;ip&quot;: &quot;203.0.113.10&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, store o ordine inesistente):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Ordine 999 non trovato su WooCommerce[negozio1].&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (500, store non configurato):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Store WooCommerce &#039;negozio1&#039; non configurato (URL/KEY/SECRET mancanti).&quot;
}</code>
 </pre>
            <blockquote>
            <p>Example response (502, upstream WooCommerce non raggiungibile):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;WooCommerce[negozio1]: dettaglio ordine 123 fallito (HTTP 500).&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-woocommerce--store--orders--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-woocommerce--store--orders--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-woocommerce--store--orders--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-woocommerce--store--orders--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-woocommerce--store--orders--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-woocommerce--store--orders--id-" data-method="GET"
      data-path="api/v1/woocommerce/{store}/orders/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-woocommerce--store--orders--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-woocommerce--store--orders--id-"
                    onclick="tryItOut('GETapi-v1-woocommerce--store--orders--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-woocommerce--store--orders--id-"
                    onclick="cancelTryOut('GETapi-v1-woocommerce--store--orders--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-woocommerce--store--orders--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/woocommerce/{store}/orders/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-woocommerce--store--orders--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-woocommerce--store--orders--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>store</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="store"                data-endpoint="GETapi-v1-woocommerce--store--orders--id-"
               value="negozio1"
               data-component="url">
    <br>
<p>Slug dello store WooCommerce. Example: <code>negozio1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-woocommerce--store--orders--id-"
               value="123"
               data-component="url">
    <br>
<p>ID ordine WooCommerce. Example: <code>123</code></p>
            </div>
                    </form>

                <h1 id="stato-servizio">Stato servizio</h1>

    

                                <h2 id="stato-servizio-GETapi-v1-health">Health check

Verifica che il servizio sia attivo. Pubblico (nessuna IP whitelist): usato dal deploy.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-health">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/health" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/health"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-health">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;status&quot;: &quot;ok&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-health" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-health"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-health"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-health" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-health">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-health" data-method="GET"
      data-path="api/v1/health"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-health', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-health"
                    onclick="tryItOut('GETapi-v1-health');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-health"
                    onclick="cancelTryOut('GETapi-v1-health');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-health"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/health</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-health"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-health"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
