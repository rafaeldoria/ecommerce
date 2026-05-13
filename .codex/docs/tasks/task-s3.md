# Task S3 - Product Image Storage Migration To AWS S3

## Purpose

Move product image uploads from the application public storage disk to AWS S3 while keeping the current catalog, admin, and storefront behavior simple.

Today product create and edit flows upload images through Laravel storage and save the resolved image URL in `products.url_img`. The implementation already has a focused boundary at `App\Modules\Catalog\ProductImages\ProductImageStorage`; this task must reuse and strengthen that boundary instead of spreading S3 logic through controllers, Livewire components, models, or Blade views.

The goal is not to build a media platform. The goal is to make product images use S3 safely, testably, and in a way that can be reused later for other product-image entry points if needed.

## Canonical Inputs

Execute this task together with:

- `.codex/environment.md`
- `.codex/docs/project.md`
- `.codex/docs/project-front.md`
- `.codex/docs/decisions.md`
- `.codex/docs/decisions-front.md`
- `.codex/docs/tasks/tasks-improve.md`

Important existing constraints:

- Catalog remains organized by `game`, `rarity`, and `product`.
- `Product.url_img` remains the image URL consumed by API resources, Livewire, and storefront views.
- Admin product create requires an image.
- Admin product edit may optionally replace the current image.
- Controllers and Livewire components stay thin.
- Business and infrastructure boundaries must stay inside the Catalog module or Laravel config.
- No payment, checkout, fulfillment, or stock behavior should change in this task.

## Current State

- `ProductImageStorage` stores uploaded files under the `products` directory on the `public` disk.
- It returns `Storage::disk('public')->url($path)` and saves that URL in `products.url_img`.
- It deletes replaced or failed uploads only when the URL looks like an owned `/storage/products/...` URL.
- API admin and Livewire admin flows both call this same storage boundary.
- The default Laravel `s3` disk exists in `config/filesystems.php`, but the S3 Flysystem adapter is not currently required in `composer.json`.
- `.env.example` already has baseline AWS variables, but there is no product-image-specific disk setting.

## Target Behavior

- Product image uploads go to AWS S3 through Laravel's filesystem abstraction.
- The admin API and Livewire admin product flows keep the same user-facing behavior:
  - create uploads an image and saves a public image URL;
  - edit can replace the image;
  - failed persistence cleans up a newly uploaded image;
  - replacing an image deletes the previous owned image when safe.
- Public catalog, product detail, cart, and admin product screens continue to render `image_url` from the backend.
- Tests must not depend on real AWS credentials or a real S3 bucket.
- Local and CI test execution must continue to use fake or local disks.

## Architecture Rules

- Keep `ProductImageStorage` as the only product-image storage boundary.
- Do not introduce a generic `StorageService`, `FileManager`, `Helper`, or root `Services` layer.
- Use Laravel `Storage` disks and configuration instead of raw AWS SDK calls.
- Add a small Catalog-owned config surface for product images, for example:
  - `config/catalog.php`
  - `catalog.product_images.disk`
  - `catalog.product_images.directory`
- Default local/test behavior should remain easy to run without AWS.
- Production should be able to set the product image disk to `s3` through environment configuration.
- Do not store AWS secrets in docs, tests, seeders, or committed env files.
- Do not make buckets public by accident in code; public URL behavior should be an explicit deployment/configuration choice.
- Keep the stored database value compatible with existing readers unless a separate migration task is approved.

## Recommended Configuration Shape

Suggested env additions:

```env
PRODUCT_IMAGE_DISK=public
PRODUCT_IMAGE_DIRECTORY=products
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Suggested behavior:

- local default: `PRODUCT_IMAGE_DISK=public`
- testing default: fake the configured product image disk
- production: `PRODUCT_IMAGE_DISK=s3`
- optional CDN/custom public base: use Laravel's existing `AWS_URL` support

If the project wants private S3 objects with signed URLs, create a separate future task. The current storefront expects stable renderable image URLs.

## Delivery Waves

### Wave S30 - S3 Dependency And Product Image Config

Goal: prepare the app to use S3 without changing upload behavior yet.

Write scope:

- `composer.json`
- `composer.lock`
- `config/catalog.php` or equivalent Catalog config file
- `config/filesystems.php` only if the existing S3 disk needs a small safe adjustment
- `.env.example`

Tasks:

- Add the Laravel S3 filesystem adapter dependency if it is still missing:
  - `league/flysystem-aws-s3-v3`
- Add product-image-specific config for disk and directory.
- Keep the default product image disk as `public` for local development.
- Add `PRODUCT_IMAGE_DISK` and `PRODUCT_IMAGE_DIRECTORY` to `.env.example`.
- Confirm existing AWS env names remain compatible with Laravel's `s3` disk.

Acceptance:

- The app can resolve product image storage config without AWS credentials.
- `composer.json` explicitly includes the S3 adapter dependency.
- Local defaults preserve current behavior until the disk is changed.

Validation:

- `docker exec ecommerce-app-1 composer validate`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`

### Wave S31 - ProductImageStorage Uses Configured Disk

Goal: make the existing product image boundary disk-agnostic and S3-ready.

Write scope:

- `app/Modules/Catalog/ProductImages/ProductImageStorage.php`
- product image exception or localization files only if needed
- product admin tests

Tasks:

- Replace hard-coded `public` disk usage with the configured product image disk.
- Replace hard-coded `products` directory usage with configured directory.
- Continue returning the resolved URL from `Storage::disk($disk)->url($path)`.
- Keep failed-store behavior explicit and translated through the existing exception path.
- Update owned-image deletion so it works for:
  - current `/storage/products/...` local URLs;
  - URLs generated from the configured S3 disk;
  - configured `AWS_URL` or disk URL when present.
- Ensure deletion never deletes outside the configured product image directory.

Acceptance:

- Store, replacement cleanup, and failed-persistence cleanup work through the configured disk.
- Existing API and Livewire callers do not need to know whether images are stored locally or in S3.
- Existing response shape remains unchanged: clients still receive `image_url`.
- Deletion is conservative: unknown URLs are ignored instead of force-deleted.

Validation:

- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 php artisan test --filter=Catalog`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

### Wave S32 - S3-Oriented Tests Without Real AWS

Goal: prove the S3 migration behavior without calling AWS.

Write scope:

- `tests/Feature/Admin/ProductsCrudTest.php`
- focused unit or feature tests for `ProductImageStorage` if the existing feature tests become too broad
- test config helpers only if needed

Tasks:

- Add tests that override `catalog.product_images.disk` to a fake disk.
- Verify product create stores the URL generated by the configured disk.
- Verify product update deletes the old owned image after a successful replacement.
- Verify failed create/update cleanup deletes only the newly uploaded file.
- Verify unknown or non-owned URLs are not deleted.
- Keep tests independent of network and credentials.

Acceptance:

- Tests fail if `ProductImageStorage` silently falls back to the hard-coded `public` disk.
- Tests cover both the successful and failure cleanup paths.
- Tests do not require an AWS account, real bucket, or internet access.

Validation:

- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 php artisan test --filter=ProductImageStorage`

### Wave S33 - Manual S3 Rollout Check

Goal: document and verify the production-like setup without committing secrets.

Write scope:

- `.env.example`
- deployment notes or wave documentation only

Tasks:

- Document the required production env values:
  - `PRODUCT_IMAGE_DISK=s3`
  - AWS key, secret, region, and bucket
  - optional `AWS_URL` for CDN/custom domain
- Confirm deployment must run config cache refresh after env changes.
- Confirm the S3 bucket/CORS/public access or CDN setup allows browser image rendering.
- Upload one product image through admin create or edit in a non-production environment configured against S3.
- Confirm storefront and admin pages render the returned image URL.
- Confirm replacing the image removes the previous owned object when allowed by bucket permissions.

Acceptance:

- S3 credentials are only present in environment/secret management.
- Product images render from S3 or the configured CDN URL.
- Replacement cleanup works or the documented deployment permission gap is recorded before release.

Validation:

- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- manual admin upload and replacement test against a non-production S3 bucket

### Wave S34 - Review And Close

Goal: close the task with project-aware review and wave documentation.

Tasks:

- Run `environment-first` at the beginning of the implementation session.
- Run `code-review` after implementation, focusing on:
  - no S3 logic leaked into controllers or Livewire;
  - no generic service layer was introduced;
  - `ProductImageStorage` remains small and explicit;
  - tests do not depend on real AWS;
  - MVP behavior outside product images did not change.
- Run `wave-docs` after implementation is complete.
- Run `wave-git-close` if that local skill is available in the workspace; if it is not available, close with the repository's normal git review and handoff flow.

Acceptance:

- Review findings are either fixed or documented.
- English and pt-BR wave docs describe what changed and what must be configured in production.
- The final diff contains only the S3 product-image migration and related tests/docs.

## Out Of Scope

- Multi-image product galleries.
- Image optimization, resizing, thumbnails, or background processing.
- Direct browser-to-S3 uploads.
- Private signed image URLs.
- CDN provisioning or Terraform/IaC.
- Migrating old local images to S3.
- Adding new product media database tables.
- Changing cart, checkout, payment, order, stock, or fulfillment behavior.
- Changing frontend design beyond ensuring existing image URLs still render.

## Open Decisions Before Production

- Will product images be served directly from the S3 bucket URL or through a CDN/custom domain?
- Should old local product images be migrated to S3 now, or can they remain until manually replaced?
- Which AWS IAM policy will be used by the application for object put, get/url generation if needed, and delete under the product image prefix?
- Does the bucket allow object deletion by the app in all environments where replacement cleanup is expected?

## Final Validation Checklist

- `docker exec ecommerce-app-1 composer validate`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 php artisan test --filter=Catalog`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`
- Manual non-production S3 upload, storefront render, replacement, and cleanup verification
