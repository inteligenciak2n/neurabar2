import fs from 'fs';
import postcss from 'postcss';

const html = fs.readFileSync('landingpage/index.html', 'utf8');
let css = html.match(/<style>([\s\S]*?)<\/style>/)[1].trim();

css = css
    .replace(/\/\* ===== CSS Reset[\s\S]*?\*\/\s*/m, '')
    .replace(/\*, \*::before, \*::after \{ box-sizing: border-box; margin: 0; padding: 0; \}\s*/m, '')
    .replace(/html\s*\{[^}]*\}\s*/g, '');

const rootMatch = css.match(/:root\s*\{([^}]*)\}/);
const rootVars = rootMatch ? rootMatch[1].trim() : '';
css = css.replace(/:root\s*\{[^}]*\}\s*/, '');

const bodyMatch = css.match(/body\s*\{([^}]*)\}/);
const bodyStyles = bodyMatch ? bodyMatch[1].trim() : '';
css = css.replace(/body\s*\{[^}]*\}\s*/, '');

css = css.replace(/(^|\n)\s*img\s*\{[^}]*\}\s*/g, '$1');
css = css.replace(/(^|\n)\s*a\s*\{[^}]*\}\s*/g, '$1');

const prefixPlugin = {
    postcssPlugin: 'prefix-welcome',
    Rule(rule) {
        if (rule.parent?.type === 'atrule' && rule.parent.name === 'keyframes') {
            return;
        }

        rule.selectors = rule.selectors.map((selector) => {
            if (selector.startsWith('.welcome-landing')) {
                return selector;
            }

            return `.welcome-landing ${selector}`;
        });
    },
};

const result = await postcss([prefixPlugin]).process(css, { from: undefined });

const header = `/* Landing page layout — scoped for Welcome.vue */
.welcome-landing {
${rootVars}
${bodyStyles}
}
.welcome-landing img { display: block; max-width: 100%; }
.welcome-landing a { text-decoration: none; color: inherit; }
html { scroll-behavior: smooth; }
`;

fs.writeFileSync('resources/css/welcome-landing.css', header + result.css);
