import { defineConfig } from 'vitepress'

const sidebar = [
  { text: 'Guide', items: [
    { text: 'Installation', link: '/guide/installation' },
    { text: 'Getting started', link: '/guide/getting-started' },
    { text: 'Routing and negotiation', link: '/guide/routing' },
  ] },
  { text: 'Reference', items: [
    { text: 'RSS', link: '/reference/rss' },
    { text: 'Atom', link: '/reference/atom' },
    { text: 'Upgrading', link: '/reference/upgrading' },
  ] },
]

export default defineConfig({
  title: 'CakePHP Feed',
  description: 'Generate RSS 2.0 and Atom 1.0 feeds from CakePHP view data.',
  base: '/cakephp-feed/',
  cleanUrls: true,
  lastUpdated: true,
  srcExclude: ['README.md', 'Install.md', 'Contributing.md', 'View/**'],
  sitemap: { hostname: 'https://dereuromark.github.io/cakephp-feed/' },
  head: [['link', { rel: 'icon', href: '/cakephp-feed/favicon.svg', type: 'image/svg+xml' }]],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'Reference', link: '/reference/rss' },
    ],
    sidebar,
    socialLinks: [{ icon: 'github', link: 'https://github.com/dereuromark/cakephp-feed' }],
    search: { provider: 'local' },
    editLink: { pattern: 'https://github.com/dereuromark/cakephp-feed/edit/master/docs/:path' },
    footer: { message: 'Released under the MIT License.', copyright: 'Copyright Mark Scherer' },
  },
})
