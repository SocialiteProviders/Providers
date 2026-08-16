# Security model

What a login enforces, and why. The short version: state (CSRF), signature, algorithm allow list, `iss`, `aud`/`azp`, `exp`/`nbf`/`iat`, `nonce`, `at_hash`, `sub`, with PKCE on by default.

## Signatures and algorithms

The allow list comes from the `jwt_algorithm` config or the OP's advertised `id_token_signing_alg_values_supported`, falling back to `RS256`. The token header's `alg` is only ever checked against that list, never trusted to select the algorithm. Deriving it from the header is the substitution attack of RFC 8725 section 2.1.

`alg: none` is always rejected, even if advertised or configured.

HS\* algorithms are rejected against a JWKS or an asymmetric key, so public material can never double as an HMAC secret (key confusion).

A token naming a `kid` the cached JWKS doesn't have triggers one refetch, which is how key rotation works without any cache tuning. `jwt_public_key` (PEM) replaces the JWKS entirely when set.

## Claims

`iss` is strict equality by default, pluggable per [issuer validators](extending.md#issuer-validators).

The token must name this client in `aud`. Multiple audiences require `azp` (OIDC Core 3.1.3.7 step 4), and a present `azp` must be this client (step 5). A token naming us as audience but authorized to a different party is not ours to accept.

`exp` is required outright. `firebase/php-jwt` only enforces expiry when the claim is present, so a token omitting it would otherwise never expire. `nbf` and `iat` are checked when present, all with `clock_skew` leeway.

The `nonce` is minted at redirect, compared at callback, and cleared after use, so a replayed id_token fails.

`at_hash` is validated against the access token when present (left-most half of the hash, OIDC Core 3.1.3.6), so a valid id_token can't be paired with someone else's access token.

`sub` is the one claim every OP must return (OIDC Core 2). A login without it is rejected.

## Transport

`base_url` must be https. Every endpoint derives from it, so a plaintext issuer would expose discovery, the JWKS and the token exchange to tampering. Loopback hosts (`localhost`, `127.0.0.1`, `::1`, `*.localhost`) are exempt so local development works.

Userinfo is consulted only when the id_token has no email. The access token travels in the Authorization header, never a query parameter (which leaks into logs and Referer headers), and the response's `sub` must match the id_token's before its claims are used (OIDC Core 5.3.2).

`client_secret_basic` credentials are form-urlencoded before base64 (RFC 6749 section 2.3.1), so secrets containing `+`, `/`, `=` or spaces work.

IdP calls default to 5s connect / 10s total timeouts, so a hanging IdP can't tie up PHP workers.

## What `verify_jwt => false` actually means

It skips only the signature check on id_tokens, leaning on OIDC Core 3.1.3.7 step 6: the token answers a request this client made over TLS, bound to the session by nonce and PKCE. Every claim check above still runs. It exists for the rare OP that can't serve a JWKS.

It never applies to back-channel logout tokens, which arrive unsolicited on an unauthenticated endpoint and are always signature-verified (see [logout](logout.md#back-channel-logout)).
