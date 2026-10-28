# Philippine address directory

`philippine-addresses.json` is a bundled snapshot downloaded on 2026-10-28 from
https://psgc.gitlab.io/api/ using the provinces, cities-municipalities, and barangays endpoints.
Only names, codes, and parent links used by the booking dropdowns are retained.
The upstream snapshot contains 81 provinces, 1,634 cities/municipalities and 42,046 barangays;
it is not a claim of current administrative boundaries. Refresh the snapshot when adopting
new PSGC changes. Metro Manila is offered as a province-level address choice using its region code.

The browser reads this local file; no guardian information is sent to the upstream provider.
