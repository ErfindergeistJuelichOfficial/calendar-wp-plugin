# Manuel Test

TEST: https://spielwiese.erfindergeist.org/calendar_test/
PROD: https://erfindergeist.org/calendar_test/

Check for following. if this not happens everything is XSS save

- No alert() popups
- <script> tags visible as escaped text, not executed
- Map links should have URL-encoded location params
- HTML in description should be escaped (visible as text)
- Sonderzeichen (ü, &, ") should display correctly