---
description: Regenerate + lint the OpenAPI spec, then remind to commit docs/openapi.json.
---

Regenerate the OpenAPI spec from code (Scramble) and validate it:

```bash
npm run openapi:export && npm run openapi:lint
```

If `docs/openapi.json` changed, remind the user it MUST be committed — the CI `openapi` drift job
fails the image build otherwise. Report any Spectral lint violations with their rule names. Do not
commit unless asked.
