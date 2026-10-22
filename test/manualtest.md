# Manuel Test

TEST: https://spielwiese.erfindergeist.org/calendar_test/
PROD: https://erfindergeist.org/calendar_test/

Check for following. if this not happens everything is XSS save

- No alert() popups
- <script> tags visible as escaped text, not executed
- Map links should have URL-encoded location params
- HTML in summary and location should be escaped (visible as text)
- HTML in description: only `a`, `br`, `p`, `b`, `strong`, `i`, `em`, `ul`, `ol`, `li` are rendered, everything else is removed (no `<script>`, `<img>`, `<iframe>`, no `onclick`, no `javascript:` links)
- Links in description open in a new tab (`target="_blank" rel="noopener noreferrer"`)
- Plain text descriptions keep their line breaks
- Hashtags are removed from the description, but not `&#39;` entities or `#fragment` in links
- Sonderzeichen (ü, &, ") should display correctly