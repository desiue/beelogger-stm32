"""MkDocs-Hook: Links aus docs/ heraus (../src/, ../README.md …) auf GitHub umbiegen.

Auf GitHub funktionieren relative Links in die übrigen Repo-Ordner, auf der Pages-Seite nicht.
Branch kommt aus der Umgebung (DOKU_BRANCH, in der Action = github.ref_name).
"""
import os, posixpath, re

REPO = "https://github.com/desiue/beelogger-stm32"
LINK = re.compile(r"(\]\()([^)\s]+)(\))")


def on_page_markdown(markdown, page, **kw):
    branch = os.environ.get("DOKU_BRANCH", "main")
    seite = posixpath.dirname(page.file.src_uri)          # z. B. "" oder "planung"

    def ersetze(m):
        ziel = m.group(2)
        if re.match(r"^[a-z]+:|^#", ziel):
            return m.group(0)
        pfad, _, anker = ziel.partition("#")
        voll = posixpath.normpath(posixpath.join("docs", seite, pfad))
        if voll.startswith("docs/") and not voll.startswith("docs/privat"):
            if not re.search(r"\.(pdf|js|json|log|diff)$", voll) and not pfad.endswith("/"):
                return m.group(0)                           # normale Doku-Seite: MkDocs regelt das
        url = f"{REPO}/blob/{branch}/{voll}" + (f"#{anker}" if anker else "")
        return m.group(1) + url + m.group(3)

    markdown = LINK.sub(ersetze, markdown)
    if branch != "main":
        hinweis = (f'!!! warning "Stand Branch `{branch}`"\n'
                   f'    Diese Seite zeigt den Arbeitsstand aus Branch `{branch}`, noch nicht auf `main` übernommen.\n\n')
        markdown = hinweis + markdown
    return markdown
