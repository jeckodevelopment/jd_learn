# Versioning policy

JD Learn follows [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html) for the version of the installable package. The authoritative source is the package manifest `packages/pkg_jlms.xml`; `component/jlms.xml` must carry the same version.

## Before 1.0.0

Versions remain `0.MINOR.PATCH` while the product is in initial development and its public API is not declared stable.

- A runtime bug fix with no new capability increments PATCH (for example, `0.42.0` → `0.42.1`).
- A backwards-compatible capability increments MINOR and resets PATCH to zero (for example, `0.42.1` → `0.43.0`).
- An incompatible public API or data contract change also increments MINOR while MAJOR is zero. The change and its migration impact must be called out in the changelog and upgrade notes.
- `1.0.0` is reserved for the release gate in [the stable plan](STABLE_1.0.0_PR_PLAN.md); it declares the public API stable.

The existing `0.41.81-dev` is historical. From the next runtime release onward, use the rules above. Documentation-only changes do not advance the installable package version.

## From 1.0.0 onward

- Increment PATCH for backwards-compatible bug fixes.
- Increment MINOR and reset PATCH to zero for backwards-compatible functionality.
- Increment MAJOR and reset MINOR and PATCH to zero for incompatible public API or data contract changes. Include migration and upgrade notes.
- A release candidate may use a SemVer pre-release identifier such as `1.1.0-rc.1`; it has lower precedence than `1.1.0`. Build metadata, if used, follows `+` and does not affect precedence.

## Repository and Joomla rules

- Source manifests use exactly three canonical numeric fields with no leading zeroes. The package and component versions must match.
- Development artifact filenames end in `-dev.zip`; `-dev` is a distribution-channel suffix, not part of the Joomla manifest version. Stable artifacts and update feeds use the exact numeric release version.
- Stable release tooling accepts only canonical stable `X.Y.Z` versions and rejects versions not greater than the current package version.
- CI checks manifest validity and package/component version equality. Bump the package and component manifests together for runtime releases; record the reason in `CHANGELOG.md`.

## References

- [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html)
- [Joomla update servers](https://manual.joomla.org/docs/4.4/building-extensions/install-update/update-server/)
- [Joomla extension manifests](https://manual.joomla.org/docs/next/building-extensions/install-update/installation/manifest/)
