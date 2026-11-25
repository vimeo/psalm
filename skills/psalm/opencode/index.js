// The Psalm plugin for OpenCode: the Psalm MCP server and skill, and on OpenCode 1 the Psalm language server.
// OpenCode 1 calls `server`, OpenCode 2 calls `setup`.
import { readFileSync } from "node:fs"
import { dirname, join } from "node:path"
import { fileURLToPath } from "node:url"

const skillDir = join(dirname(fileURLToPath(import.meta.url)), "..")
const launcher = join(skillDir, "scripts", "server.php")
const php = process.env.PSALM_PHP || "php"

/** @param {string} mode @param {string} directory */
const command = (mode, directory) => [php, launcher, mode, directory]

/** @param {{ directory: string }} input */
async function server({ directory }) {
  return {
    /** @param {Record<string, any>} config */
    async config(config) {
      config.mcp ??= {}
      config.mcp.psalm ??= { type: "local", command: command("mcp", directory), enabled: true }

      // OpenCode 1 runs no language server unless the config has an `lsp` section
      if (config.lsp && typeof config.lsp === "object") {
        config.lsp.psalm ??= { command: command("lsp", directory), extensions: [".php"] }
      }
    },
  }
}

/** @param {any} ctx */
async function setup(ctx) {
  const directory = ctx.location.directory

  await ctx.mcp.transform((editor) => {
    if (!editor.get("psalm")) {
      editor.set("psalm", { type: "local", command: command("mcp", directory), codemode: false })
    }
  })

  const path = join(skillDir, "SKILL.md")
  const skill = readFileSync(path, "utf8")
  const frontmatter = skill.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n/)
  const field = (name) => frontmatter?.[1].match(new RegExp(`^${name}: (.*)$`, "m"))?.[1].trim() ?? ""

  await ctx.skill.transform((editor) => {
    if (!editor.get("psalm")) {
      editor.add({
        id: "psalm",
        name: field("name"),
        description: field("description"),
        path,
        content: frontmatter ? skill.slice(frontmatter[0].length) : skill,
      })
    }
  })
}

export default { id: "psalm", setup, server }
