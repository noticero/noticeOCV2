# NoticeConfirm — Plugin OpenCart pentru Confirmare Comenzi prin notice.ro

Plugin OpenCart 2.x / 3.x care automatizează confirmarea comenzilor printr-un flux în 4 pași: **apel vocal → WhatsApp → SMS → recall**, folosind platforma [notice.ro](https://notice.ro).

---

## Cuprins

1. [Cerințe](#cerințe)
2. [Instalare](#instalare)
3. [Configurare](#configurare)
4. [Setarea cronului](#setarea-cronului)
5. [Fluxul de confirmare](#fluxul-de-confirmare)
6. [Template-uri de mesaje](#template-uri-de-mesaje)
7. [Callback notice.ro](#callback-noticero)
8. [Structura bazei de date](#structura-bazei-de-date)
9. [Depanare](#depanare)

---

## Cerințe

| Cerință | Versiune minimă |
|---|---|
| OpenCart | 2.3+ sau 3.x |
| PHP | 7.1+ |
| Extensie PHP | `curl`, `json` |
| Cont notice.ro | [api.notice.ro](https://api.notice.ro) |

---

## Instalare

### OpenCart 3.x (OCMOD)

1. Descarcă fișierul `noticeconfirm.ocmod.zip` din [pagina de releases](../../releases).
2. În panoul de administrare OpenCart mergi la **Extensions → Installer**.
3. Apasă **Upload** și selectează `noticeconfirm.ocmod.zip`.
4. După upload, mergi la **Extensions → Modifications** și apasă butonul **Refresh** (iconița ↺ din dreapta sus).
5. Mergi la **Extensions → Extensions**, selectează tipul **Modules**.
6. Găsește **NoticeConfirm** în listă și apasă **Install** (butonul verde +).
7. Apasă **Edit** (butonul albastru cu creion) pentru a deschide setările.

### OpenCart 2.x (manual)

1. Dezarhivează `noticeconfirm.ocmod.zip`.
2. Copiază conținutul folderului `upload/` în directorul rădăcină al magazinului tău OpenCart.
3. În panoul de administrare mergi la **Extensions → Modules**.
4. Găsește **NoticeConfirm** și apasă **Install**, apoi **Edit**.

---

## Configurare

Deschide **Extensions → Modules → NoticeConfirm → Edit**.

### API Settings

| Câmp | Descriere |
|---|---|
| **Status** | Activează sau dezactivează modulul. |
| **Bearer Token** | Token-ul de autentificare din contul tău [api.notice.ro](https://api.notice.ro). Îl găsești în secțiunea *API Keys* din panoul notice.ro. |
| **Callback URL** | Completat automat. Copiază această adresă și introdu-o în setările contului notice.ro ca URL de callback pentru apeluri vocale. |

### Timing

| Câmp | Descriere | Implicit |
|---|---|---|
| **Call window** | Intervalul orar în care se efectuează apeluri (ex: 10 – 21). | 10 – 21 |
| **Min. order age** | Câte minute trebuie să aibă comanda înainte de primul apel. | 2 min |
| **Call → WhatsApp delay** | Minute de așteptare după apel înainte de a trimite WhatsApp. | 15 min |
| **WhatsApp → SMS delay** | Minute de așteptare după WhatsApp înainte de SMS. | 15 min |
| **SMS → Recall delay** | Minute de așteptare după SMS înainte de al doilea apel vocal. | 15 min |
| **Skip if previous cancellations >** | Dacă un număr de telefon are mai multe comenzi anulate decât această valoare, este ignorat. | 1 |

### Order Status Mapping

Asociează statusurile din OpenCart cu etapele fluxului:

| Câmp | Descriere |
|---|---|
| **Trigger on status(es)** | Comenzile în aceste statusuri intră în fluxul de confirmare. Selectează multiple cu Ctrl/Cmd. |
| **Confirmed status** | Statusul aplicat când clientul apasă tasta **1** (confirmare) în timpul apelului vocal. |
| **Cancelled status** | Statusul aplicat când clientul apasă tasta **9** (anulare) în timpul apelului vocal. |
| **History/notify status** | Statusul folosit la înregistrarea în istoricul comenzii a notificărilor trimise (apel, WhatsApp, SMS). |
| **Refused/not-picked-up status** | Statusul pentru comenzi refuzate sau la care nu s-a răspuns. Folosit pentru filtrul *Skip if previous cancellations*. |

### Message Templates

Permite personalizarea mesajelor trimise în fiecare etapă.

**Variabile disponibile:**

| Variabilă | Descriere |
|---|---|
| `{order_id}` | ID-ul comenzii |
| `{total}` | Valoarea totală a comenzii |
| `{firstname}` | Prenumele clientului |
| `{lastname}` | Numele de familie al clientului |
| `{telephone}` | Numărul de telefon |
| `{email}` | Adresa de email |
| `{date_added}` | Data plasării comenzii |
| `{token}` | Link de confirmare (recomandat doar pentru SMS) |

**Cum încarci template-uri din notice.ro:**

1. Apasă butonul **Load templates from notice.ro** din panoul *Message Templates*.
2. Din dropdown-ul care apare, selectează un template salvat în contul tău notice.ro.
3. Apasă **Apply to Call / WhatsApp / SMS / Recall** pentru a-l atribui etapei dorite.
4. Editează textul dacă este necesar și adaugă variabile din lista de mai sus.
5. Lasă câmpul gol pentru a folosi textul implicit al pluginului.

---

## Setarea cronului

Pluginul folosește un script cron care trebuie rulat **la fiecare 1–5 minute**.

### Calea scriptului

```
/cale/catre/opencart/system/library/noticeconfirm_cron.php
```

### Adăugare în crontab

```bash
crontab -e
```

Adaugă linia:

```
*/2 * * * * php -f /var/www/html/system/library/noticeconfirm_cron.php >> /var/log/noticeconfirm.log 2>&1
```

> Înlocuiește `/var/www/html/` cu calea reală a instalării OpenCart.

### Testare manuală

```bash
php -f /var/www/html/system/library/noticeconfirm_cron.php
```

Scriptul nu produce niciun output dacă nu există comenzi de procesat.

---

## Fluxul de confirmare

Scriptul cron procesează fiecare comandă aflată în statusurile de trigger și care nu a fost confirmată (`confirmata != 1`):

```
Comandă nouă (status = Pending)
        │
        ▼ (după min_age minute, în fereastra orară)
   ┌─────────────┐
   │  STEP 1     │  Apel vocal
   │  Voice Call │  Tasta 1 = Confirmat | Tasta 9 = Anulat
   └──────┬──────┘
          │ (dacă nu răspunde, după delay_wapp minute)
          ▼
   ┌─────────────┐
   │  STEP 2     │  Mesaj WhatsApp
   │  WhatsApp   │
   └──────┬──────┘
          │ (după delay_sms minute)
          ▼
   ┌─────────────┐
   │  STEP 3     │  SMS cu link de confirmare
   │  SMS        │  Variabila {token} = link unic
   └──────┬──────┘
          │ (dacă nu acționează, după delay_recall minute, în fereastra orară)
          ▼
   ┌─────────────┐
   │  STEP 4     │  Al doilea apel vocal (Recall)
   │  Recall     │
   └─────────────┘
```

Fluxul se oprește automat dacă:
- Clientul confirmă comanda (tasta 1 sau link SMS)
- Clientul anulează comanda (tasta 9)
- Comanda iese din statusul de trigger (modificată manual)

---

## Callback notice.ro

Platforma notice.ro trimite un POST la **Callback URL** (din setările pluginului) după fiecare apel vocal finalizat.

**URL callback:**
```
https://domeniu.ro/index.php?route=extension/module/noticeconfirm_callback/callback
```

**Parametri primiți:**

| Parametru | Valoare |
|---|---|
| `audio_id` | ID-ul apelului vocal |
| `status` | `confirmed`, `cancelled`, `no_answer`, `no_response`, `failed` |

**Comportament:**
- `confirmed` → comanda trece la statusul *Confirmed*, câmpurile `confirmata=1` și `confirm_date` sunt setate.
- `cancelled` → comanda trece la statusul *Cancelled*.
- Altele → marcate ca `no_answer` în tabelul `ockg_audio`.

> Asigură-te că URL-ul de callback este accesibil public (nu blocat de firewall sau autentificare HTTP).

---

## Structura bazei de date

La instalare, pluginul creează tabelul `ockg_audio`:

| Coloană | Tip | Descriere |
|---|---|---|
| `id` | INT | Cheie primară |
| `order_id` | INT | ID-ul comenzii |
| `audio_id` | INT | ID-ul apelului returnat de notice.ro |
| `called` | TINYINT | 1 dacă apelul a fost efectuat |
| `whatsapp` | TINYINT | 1 dacă WhatsApp-ul a fost trimis |
| `sms` | TINYINT | 1 dacă SMS-ul a fost trimis |
| `result` | INT | NULL=în așteptare, 1=confirmat, 9=anulat, 0=fără răspuns, -1=recall în curs |
| `text` | TEXT | Textul folosit la apel |
| `call_date` | DATETIME | Data/ora primului apel |
| `whatsapp_date` | DATETIME | Data/ora WhatsApp |
| `sms_date` | DATETIME | Data/ora SMS |

---

## Depanare

### Template-urile nu se încarcă

- Verifică că Bearer Token-ul este salvat corect în setările pluginului.
- Verifică că serverul are acces la internet și extensia `curl` este activată.
- Verifică în consola browser-ului (F12 → Network) cererea către `noticeconfirm/templates` pentru a vedea răspunsul serverului.

### Cronul nu procesează comenzile

- Verifică că statusul comenzilor corespunde cu cele selectate în **Trigger on status(es)**.
- Verifică că modulul este activat (Status = Enabled).
- Rulează manual scriptul și verifică logurile:
  ```bash
  php -f /cale/opencart/system/library/noticeconfirm_cron.php
  ```
- Verifică logurile din `system/storage/logs/` pentru erori.

### Apelurile nu se efectuează

- Verifică fereastra orară (Call window) — scriptul nu apelează în afara intervalului configurat.
- Verifică că Bearer Token-ul notice.ro are credit suficient.
- Verifică că numărul de telefon al clientului are 10 cifre (format românesc: 07XXXXXXXX).

### Callback-ul nu funcționează

- Asigură-te că URL-ul de callback din setările notice.ro este exact cel afișat în câmpul **Callback URL** din plugin.
- Verifică că URL-ul este accesibil public (testează cu un browser din afara rețelei).
- Verifică logurile PHP pentru erori la ruta `extension/module/noticeconfirm_callback/callback`.

### Resetare stare notificare pentru o comandă

Dacă vrei să repornești fluxul pentru o comandă, șterge înregistrarea din `ockg_audio`:

```sql
DELETE FROM ockg_audio WHERE order_id = <ID_COMANDA>;
```

---

## Licență

MIT License. Plugin creat pentru integrarea cu platforma [notice.ro](https://notice.ro).
