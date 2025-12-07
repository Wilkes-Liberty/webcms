# Headless CMS: GraphQL-only + Navigation + Maintenance

This Drupal 11 site serves as the API backend for a Next.js frontend. We use GraphQL exclusively (no JSON:API). The Main menu is the canonical source of navigation.

## API choices
- GraphQL (preferred)
  - Enabled: `graphql`, `graphql_compose`, `graphql_compose_menus`, `graphql_core_schema`, etc.
  - Endpoint: `/graphql`
- JSON:API
  - Disabled/uninstalled: `jsonapi`, `next_jsonapi`, and composer package `drupal/jsonapi_extras` removed.

## Navigation model
- Canonical source: Main menu (`menu_link_content`). Editors can drag/drop, rename, or add links. The Next.js frontend should consume this menu directly via GraphQL.
- Taxonomies (Capabilities, Industries, etc.) remain for content organization and term pages.
- A small utility exists to sync top-level taxonomy terms (Capabilities/Industries) into the Main menu when the term checkbox "Show in navigation?" is enabled — useful if you want a quick seed. Use it on-demand; the wl_taxo_nav module stays disabled by default.

## URLs for Capabilities
- Capabilities term pages live at `/capabilities/*`.
- 301 redirects were created from the old `/services/*` paths.

## GraphQL for Next.js
A simple GraphQL query to fetch the Main menu tree:

```graphql
query MainMenu {
  menu(name: "main") {
    name
    items {
      title
      url
      children {
        title
        url
        children { title url }
      }
    }
  }
}
```

Example Next.js (App Router) data fetcher using built-in `fetch` and ISR:

```ts
// app/lib/cms.ts
export async function getMainMenu() {
  const res = await fetch(process.env.CMS_GRAPHQL_URL!, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      query: `
        query MainMenu {
          menu(name: "main") {
            items { title url children { title url children { title url } } }
          }
        }
      `,
    }),
    next: { revalidate: 300 }, // ISR: revalidate every 5 minutes
  });
  const { data } = await res.json();
  return data.menu.items as { title: string; url: string; children?: any[] }[];
}
```

Example server component that renders the menu recursively:

```tsx
// app/components/MainNav.tsx
import Link from 'next/link';
import { getMainMenu } from '@/app/lib/cms';

function MenuList({ items }: { items: { title: string; url: string; children?: any[] }[] }) {
  return (
    <ul>
      {items.map((item, idx) => (
        <li key={idx}>
          <Link href={item.url}>{item.title}</Link>
          {item.children?.length ? <MenuList items={item.children} /> : null}
        </li>
      ))}
    </ul>
  );
}

export default async function MainNav() {
  const items = await getMainMenu();
  return <nav><MenuList items={items} /></nav>;
}
```

Environment variables (Next.js):

```
CMS_GRAPHQL_URL=https://cms.ddev.site/graphql
```

Adjust for your deployment domains.

## Maintenance scripts
Located in `scripts/` at the Drupal project root.

- `hugo_export.mjs`
  - Scans the Hugo repo and emits JSON files into `web/modules/custom/wl_hugo_migrate/data/` for terms, pages, menus, and redirects.
  - Run: `node scripts/hugo_export.mjs`

- `import_hugo.php`
  - One-off importer that reads the emitted JSON and creates/updates taxonomy terms, pages, menu links, and redirects.
  - Run: `ddev drush scr scripts/import_hugo.php`

- `menu_remove_services.php`
  - Removes the top-level "Services" item from the Main menu.
  - Run: `ddev drush scr scripts/menu_remove_services.php`

- `switch_capabilities_urls.php`
  - Switches Capabilities term aliases to `/capabilities/*` and creates 301 redirects from previous `/services/*` paths.
  - Run: `ddev drush scr scripts/switch_capabilities_urls.php`

- `wl_taxo_nav_sync.php`
  - On-demand sync for top-level Capabilities/Industries terms (when "Show in navigation?" is checked) into the Main menu. Does NOT require enabling the module.
  - Run: `ddev drush scr scripts/wl_taxo_nav_sync.php`

## Config management
- Export changes: `ddev drush cex -y`
- Import changes: `ddev drush cim -y`
- Clear caches: `ddev drush cr`

