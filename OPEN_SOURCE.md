# Open-source publication and project status

Pislia Dzvonyka is an educational portfolio project. The author has decided not to operate it as a service for real schools. Deployment instructions describe the software's capabilities, not an invitation to enroll schools or a promise of support.

## Terms of use

The code is provided under the MIT License (LICENSE). Use, modification, redistribution and sale are permitted with the copyright and license notice preserved. Use is free of charge and no separate permission is needed. See AUTHORS.md for authorship and AI assistance.

The software is provided “as is”, without warranty. The author does not promise support, updates, hosting availability or fixes within a specified period. The license's liability limitation is not a blanket exemption from every legal obligation. It does not remove obligations concerning data the author actually processes.

Anyone operating their own instance must arrange data protection, backups, lawful processing grounds, appropriate contact information and site policies. Before launch, replace the author details in config/site.php with those of your instance's operator. Do not present the original author as support for your service. The personal contact email has been removed from this portfolio version; configure a real operator contact before handling real users' data.

## Before publishing on GitHub

- Publish only code, documentation and fictional sample data. Exclude config/config.php, .env, real database dumps, logs, sessions, FTP credentials, OAuth secrets and tokens.
- Collected school contacts, their generation script and the old local Git history have been deleted. Remaining outreach/ materials are excluded from Git. No invitations have been sent.
- Review both current files and any future Git history. A .gitignore rule does not remove tracked files or old secrets. Revoke or rotate exposed secrets even after deleting their history.
- Review README, LICENSE, AUTHORS.md and the staged file list before publishing. These notes are not a completed independent security audit.
- For an online demonstration, use fictional records and an isolated environment. Do not expose shared administrator access to a real database.
- GitHub can host source code; GitHub Pages cannot run PHP or MySQL. A live demo needs PHP hosting, or use screenshots/video in the portfolio.

## Third-party components

Check licenses and retain required notices before redistributing third-party libraries or copies. Quill has its own license. Google OAuth is an external service with separate terms. No claim is made to authorship of third-party code.
