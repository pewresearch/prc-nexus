# Contributing to WP Feature API

## Building the Project

1. **Clone the repository:**

    ```bash
    git clone https://github.com/Automattic/wp-feature-api.git
    cd wp-feature-api
    ```

2. **Install dependencies:**
    This project uses npm workspaces. Dependencies are installed from the root directory.

    ```bash
    npm install
    ```

    ```bash
    composer install
    ```

3. **Build the project:**
    To build the JavaScript and CSS assets for the plugin and its packages:

    ```bash
    npm run build
    ```

## Release Process

Releasing a new version involves several steps:

1. **Ensure the `trunk` branch is stable and all changes for the release are merged.**

2. **Create a Branch and Push Changes:**
   Create a new branch for the release (e.g., `release/vX.Y.Z`).

    ```bash
    git checkout -b release/vX.Y.Z
    ```

3. **Update Version Numbers:**
    Manually update the version number in the following files to the new version (e.g., `1.2.3`):
    - [`package.json`](package.json): Update the `version` field.

        ```json
        {
          "name": "wp-feature-api",
          "version": "NEW_VERSION_HERE",
          // ...
        }
        ```

    - [`wp-feature-api.php`](wp-feature-api.php): Update the version in two places:
        - Plugin header comment:

            ```php
            /**
             * ...
             * Version: NEW_VERSION_HERE
             * ...
             */
            ```

        - PHP constant definition:

            ```php
            define( 'WP_FEATURE_API_VERSION', 'NEW_VERSION_HERE' );
            ```

4. **Commit Version Bump:**
    Commit these version changes with a message like `Update version to X.Y.Z`.

    ```bash
    git add package.json wp-feature-api.php
    git commit -m "Update version to X.Y.Z"
    ```

5. **Push Changes:**
    After committing the version bump, push the changes to the remote repository.

    ```bash
    git push origin release/vX.Y.Z
    ```

6. **Create a Pull Request:**
    Open a pull request from your `release/vX.Y.Z` branch to the `trunk` branch.

7. **Merge the Pull Request:**
    Once the pull request is approved, merge it into the `trunk` branch.

8. **Create and Publish GitHub Release:**
    Once the version bump Pull Request is merged into `trunk`:
    1. Go to the "Releases" page in the GitHub UI.
    2. Click the "Draft a new release" button.
    3. In the "Choose a tag" dropdown, type your new version tag (e.g., `v1.2.3`). GitHub will offer to "Create new tag: vX.Y.Z on publish". Select this.
    4. Ensure the "Target" is the `trunk` branch.
    5. Enter a "Release title" (e.g., `Version 1.2.3` or `v1.2.3`).
    6. Write a description for the release. You can list the major changes, or use GitHub's auto-generated release notes feature if available/configured.
    7. Click "Publish release".
    This action will create the new tag from `trunk` and trigger the GitHub Actions workflow defined in [`.github/workflows/release.yml`](.github/workflows/release.yml:1).

9. **Verify Release:**
    After the GitHub Actions workflow completes, check the "Releases" page on GitHub to ensure everything completed successfully.
