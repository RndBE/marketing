# Cross-Company Owner Quotation Edit Design

## Context

CRM already preserves access to a user's own quotations after the user moves to another company. This behavior was introduced for the Akhmad Zaeni Mustofa case: a quotation remains listed and viewable when its `id_user` still points to the moved user, even though the quotation's `company_id` remains the original company.

Editing is still blocked because `ensurePenawaranEditAccess()` validates the quotation company before checking ownership. As a result, a moved owner can view an old quotation but receives HTTP 403 when attempting to edit it.

## Goal

Allow the original owner of a quotation to continue editing that quotation after moving companies, without changing the quotation's issuing company and without granting cross-company edit access to other users.

## Access Rules

1. Admin users retain their existing unrestricted access.
2. A user with the `edit-penawaran` permission may edit a quotation when `penawaran.id_user` equals the authenticated user's ID, even when their current `company_id` differs from the quotation's `company_id`.
3. A non-owner may edit only under the existing same-company rule.
4. Company visibility sharing remains view-only and does not grant edit access.
5. The quotation's `company_id`, document number, issuer identity, approval history, and signatures are not migrated when the user moves.
6. Deleting the entire quotation or requesting its deletion remains subject to the original company restriction. This change opens content editing, not cross-company document destruction.

## Implementation Shape

Separate content-edit authorization from destructive quotation authorization:

- Update the content edit guard to allow an authenticated owner before applying the company restriction.
- Preserve or introduce a strict destructive-action guard for `destroy()` and `requestDelete()` so those actions continue requiring same-company access unless the actor is an admin.
- Keep route permission middleware authoritative. Ownership does not replace the `edit-penawaran` permission.
- Continue using the quotation's own `company_id` for issuer rendering and stored document identity.

The content edit guard covers the header, cover, validity, pricing, items, item details, terms, signatures, attachments, ordering, approval submission, and goal state because those endpoints already use the centralized edit guard.

## Alternatives Considered

### Grant edit through company visibility

Rejected because visibility is currently a read-sharing mechanism. Treating it as edit permission would let unrelated users in a shared company modify the document.

### Move old quotations to the user's new company

Rejected because it would change the legal issuer, numbering context, branding, and potentially approval history of an existing quotation.

### Give the moved user an admin role

Rejected because it grants substantially broader access than this use case requires.

## Test Coverage

Feature tests will prove that:

- A moved owner with `edit-penawaran` can open the edit page and update their old quotation.
- A moved owner without `edit-penawaran` remains blocked by middleware.
- Another user in the new company cannot edit the old quotation.
- A user who can only see the quotation through company visibility cannot edit it.
- The moved owner cannot delete or request deletion of the old cross-company quotation.
- Same-company editing and admin editing continue to work.

## Success Criteria

The Zaeni regression scenario expands from "can still view" to "can still view and edit" for the original owner, while issuer identity and tenant boundaries for all other users remain unchanged.
