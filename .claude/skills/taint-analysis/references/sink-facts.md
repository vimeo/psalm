# Verified sink facts

## curl options (PHP 8.5 / libcurl 8.22, checked against a raw socket server)

Implemented in `ArgumentsAnalyzer` (`CURL_OPTION_SINKS` maps each option to its kinds,
`CURL_ANY_OPTION_SINKS` applies to a non-constant option, `CURL_SINK_NAMES` names the
per-kind nodes). `curl_setopt_array` keys come from `Config::getPredefinedConstants()`.
Without the curl extension the edge is a keyless `arrayvalue-fetch`, which over-reports.
Options the running extension doesn't define are skipped, and aliases are deduped by
value.

- **header** (CR/LF sent as is): `HTTPHEADER`, `PROXYHEADER`, `USERAGENT`, `REFERER`,
  `COOKIE`, `ACCEPT_ENCODING`/`ENCODING`, `RANGE`, `XOAUTH2_BEARER`, `CUSTOMREQUEST`
  (smuggles a second request), `REQUEST_TARGET` (`header|ssrf`), `AWS_SIGV4`,
  `RTSP_STREAM_URI`/`SESSION_ID`/`TRANSPORT`, `QUOTE`/`PREQUOTE`/`POSTQUOTE` (arbitrary
  FTP/SFTP commands), `COOKIELIST`.
- **Safe:** `POSTFIELDS` (Content-Length); `USERPWD`/`PROXYUSERPWD`, `SASL_AUTHZID`,
  `PROXYUSERNAME` (encoded); FTP `USERNAME`/`PASSWORD`, `FTP_ACCOUNT`,
  `FTP_ALTERNATIVE_TO_USER`, SMTP `MAIL_FROM`/`MAIL_RCPT`, `LOGIN_OPTIONS` (libcurl
  rejects CR/LF); `HTTP200ALIASES` (response-only). A raw CR/LF in a URL is rejected.
- **callable:** callback options accept any function name.
- **file:** `COOKIEJAR` writes a chosen path with server-controlled content.
  `CURLFile::__construct`/`curl_file_create` have `file` sinks in `InternalTaintSinkMap`.
- **sleep:** `MAX_RECV_SPEED_LARGE`.
- **ssrf:** `DEFAULT_PROTOCOL=file`, `REDIR_PROTOCOLS_STR=all`, `NOPROXY=*`.
  `CURLOPT_URL` and `curl_init` are `ssrf|file` (`file://` is read by default);
  `UNIX_SOCKET_PATH` is `ssrf|file`.
- Kept single on purpose: `ALTSVC` (`file`), `HTTPHEADER` (`header`; adding `ssrf` would
  double-report).
- Left out on purpose: `RANDOM_FILE`/`EGDSOCKET` (deprecated no-ops), `LOW_SPEED_*`,
  `HAPPY_EYEBALLS_TIMEOUT_MS`, `FTPPORT`, `TELNETOPTIONS`, certificate blobs and
  cipher/pin options (no kind fits).
- Nothing in Psalm removes `header`; users need `@psalm-taint-escape header`.

## URL kinds (`url_component`, `url_path`)

- After a literal URL start that fixes the server (`http(s)/ftp(s)://host` followed by
  `/`, `?` or `#`, or `//host/`), `ssrf` is removed (and `file` for network schemes).
  Other schemes are stream wrappers (`php://filter/resource=`,
  `compress.zlib://https://`) and remove nothing.
- What's left is reported, not dropped:
  - `url_component` (URL syntax characters → `TaintedUrlComponent`) is escaped by
    `urlencode`/`rawurlencode`/`http_build_query`.
  - `url_path` (a `..` segment survives encoding → `TaintedUrlPath`) is removed only when
    a `?`/`#` follows the host. `http_build_query` escapes it, since its output always
    contains `=`.
- Literal prefixes of concats/interpolations are stored by
  `NodeDataProvider::setLiteralPrefixes()` (rewritten on every pass).
- sprintf args formatted only numerically (`%d`, `%x`, `%f`, not `%s`/`%c`) lose
  `ALL_INPUT & ~NUMERIC_ONLY`. It bails on `*`, unpacking and named args.

## Scalar casts

- `Union::getTaintsToRemove()`:
  - int/float strip all input taints except `NUMERIC_ONLY` (`sleep` survives);
  - bool strips all except `BOOL_ONLY`;
  - string strips `ARRAY_ONLY` (only `nosql`).
- It normally applies at the argument boundary (`ArgumentAnalyzer::processTaintedness`).
  A cast inside an array literal is covered by `CastAnalyzer::stripCastTaints()`'s
  `<type>-cast` node, in every graph mode.
