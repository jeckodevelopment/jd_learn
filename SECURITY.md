# Security Policy

JD Learn is under active development. No stable release has been published yet; development snapshots and `-dev` packages are not supported for production use.

## Supported versions

Once the first stable release is published, security fixes will be provided for the latest stable minor release line. Older minor lines and development builds will not receive security updates unless a release notice explicitly says otherwise.

| Release line | Support |
| --- | --- |
| Latest stable minor (`1.0.x` after its release) | Supported |
| Earlier minor releases | Not supported |
| Development snapshots (`*-dev`) | Not supported for production |

## Reporting a vulnerability

Please report suspected vulnerabilities privately through [GitHub Security Advisories](https://github.com/jeckodevelopment/jd_learn/security/advisories/new). Do not disclose exploitable details in a public issue or pull request. If private vulnerability reporting is unavailable for your account, contact the repository owner privately through GitHub and request a secure reporting channel.

Include the affected JD Learn version, Joomla/PHP/database versions, a concise impact description, and reproducible steps that use only a local or disposable test site. Do not include real student data, credentials, session cookies, or production secrets.

The maintainers will acknowledge a report within five business days, confirm triage status within ten business days, and coordinate a fix and disclosure timeline with the reporter. Timelines may vary with severity, exploitability, and release constraints. Please allow maintainers to prepare and distribute a fix before public disclosure.

## Security response

Reports are triaged for affected versions, exploitability, impact, and required mitigations. Confirmed issues are tracked privately until a fix or mitigation is available. Public release notes will describe the affected versions, impact, and upgrade or mitigation steps without exposing unnecessary exploit details. Security fixes are validated against supported Joomla and PHP/database combinations before release.

This policy describes the project's reporting and support process; it is not a security certification or a claim that all risks have been eliminated. See the [1.0.0 security and privacy release gate](docs/SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md) for planned security, privacy, cookie, and acceptance work.
