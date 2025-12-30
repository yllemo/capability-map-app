# SECURITY.md

## Säkerhetsriktlinjer

### Rapportera säkerhetsproblem

Om du hittar en säkerhetsbrist i detta projekt, vänligen:

1. **Rapportera inte säkerhetsproblem offentligt** via GitHub Issues
2. Skicka ett privat meddelande eller e-post till projektets underhållare
3. Inkludera en detaljerad beskrivning av problemet
4. Inkludera steg för att reproducera problemet

### Säkerhetsbästa praxis

#### För produktionsmiljöer:

**KRITISKT: Ändra standardlösenord**
- Ändra omedelbart lösenordet i `config/auth.php`
- Använd minst 32 tecken långa slumpmässiga lösenord
- Överväg att använda en lösenordshanterare

**Webserver-säkerhet**
- Använd HTTPS i produktion
- Begränsa åtkomst till `/editor` med brandvägg eller .htaccess
- Se till att `storage/` mappen inte är webbtillgänglig

**Filrättigheter**
```bash
# Rekommenderade rättigheter
chmod 644 config/*.php
chmod 755 view/ editor/
chmod 700 storage/ (om den används)
```

**Backup och övervakaning**
- Säkerhetskopiera `/content` regelbundet
- Övervaka `/storage/error.log` för misstänkta aktiviteter
- Implementera loggrotering

#### Säkerhetsfeatures som redan är implementerade:

✅ **CSRF-skydd** - Alla editor-formulär har CSRF-tokens
✅ **Path traversal-skydd** - PathGuard klass förhindrar directory traversal
✅ **Session-säkerhet** - Säkra cookies med HttpOnly, SameSite
✅ **Audit logging** - All editor-aktivitet loggas
✅ **Input validation** - Alla inputs valideras och saniteras

### Systemkrav för säkerhet

- PHP 8.0+ (för bättre säkerhetsfeatures)
- Webserver med HTTPS-stöd
- Skrivrättigheter endast för content och storage

### Kända begränsningar

- **Enkelt lösenordsskydd**: Applikationen använder endast ett lösenord för editor-åtkomst
- **Ingen användarhantering**: Inga roller eller multipla användare
- **Filbaserad lagring**: Ingen databas-integrering

### Rapporterade problem

Inga säkerhetsproblem har rapporterats än.

---

**Version**: 1.0
**Senast uppdaterad**: 2025-12-30