import camelot
import mysql.connector
import os
import re
from dotenv import load_dotenv, dotenv_values 

load_dotenv()  ## charge les var du .env
PDF_PATH = "PASS_Résultats_PBI_S1-1.pdf"

# --- CONFIGURATION MYSQL ---
DB_CONFIG = {
    'host': os.getenv("HOST"),
    'user': os.getenv("USER"),       
    'password': os.getenv("PASSWORD"),
    'database': os.getenv("DB"),
    'port': os.getenv("PORT")
}

CURRENT_SEMESTRE = {
    "code": "PBI_OCT_2025",
    "nom": "Partiels Blancs Intermédiaires - Octobre 2025",
    "annee": 2025,
    "date": "2025-10-28"
}

def clean_value(val):
    """Nettoie une valeur brute."""
    if not val: return None
    val = val.replace(",", ".").replace("Note sur 20", "").strip()
    if val in ["DEF", "ABS", "ABSENT", "-", "","#VALEUR!"]:
        return "DNF"
    try:
        match = re.search(r"(\d+\.?\d*)", val)
        if match:
            return float(match.group(1))
    except:
        pass
    return None

def parse_pdf():
    if not os.path.exists(PDF_PATH):
        raise FileNotFoundError(f"PDF introuvable : {PDF_PATH}")

    print("Extraction et analyse intelligente des filières...")
    tables = camelot.read_pdf(PDF_PATH, pages='all', flavor='stream')
    students = []
    
    for table in tables:
        df = table.df
        for _, row in df.iterrows():
            raw_row = [str(x).strip() for x in row.values]
            row_text = " ".join(raw_row).upper()
            
            # 1. Détection de l'ID
            id_etudiant = None
            start_index = -1
            for i, cell in enumerate(raw_row):
                if re.match(r"^22\d{6}", cell):
                    id_etudiant = int(re.sub(r"[^\d]", "", cell))
                    start_index = i
                    break
            
            if not id_etudiant: continue

            # 2. Détection de la Filière
            filiere = "MMOK" if "MMOK" in row_text else "PHARMA" if "PHARMA" in row_text else "AUTRE FILIERE"

            # 3. Récupération des notes (valeurs non vides uniquement)
            # On ignore les colonnes vides qui causent le décalage
            values = [x for x in raw_row[start_index+1:] if x.strip() != ""]
            cleaned_values = [clean_value(v) for v in values]
            # On filtre les None qui ne sont pas des DNF (garder DNF et floats)
            notes = [v for v in cleaned_values if v is not None]

            # 4. MAPPING DYNAMIQUE SELON LA FILIERE
            # Les 9 premières valeurs sont toujours les mêmes (UE1...UE10)
            # Structure commune : UE1, UE2, UE3, UE7(4 notes), UE8, UE10 -> 9 notes
            
            record = {
                "id_etudiant": id_etudiant,
                "filiere": filiere,
                # Socle Commun
                "UE1": notes[0] if len(notes) > 0 else None,
                "UE2": notes[1] if len(notes) > 1 else None,
                "UE3": notes[2] if len(notes) > 2 else None,
                "UE7_CA": notes[3] if len(notes) > 3 else None,
                "UE7_CG": notes[4] if len(notes) > 4 else None,
                "UE7_CO": notes[5] if len(notes) > 5 else None,
                "UE7_TOTAL": notes[6] if len(notes) > 6 else None,
                "UE8": notes[7] if len(notes) > 7 else None,
                "UE10": notes[8] if len(notes) > 8 else None,
                
                # Initialisation des spécifiques
                "UE12_HDM": None, "UE12_ANATOMIE": None, "UE12_TOTAL": None,
                "UE13": None,
                "MOYENNE_MMOK": None, "MOYENNE_PHARMA": None
            }

            # Gestion Spécifique
            if filiere == "MMOK":
                # MMOK : Après UE10 (index 8), on attend UE12 (3 notes) + Moyenne (1 note)
                # Indices attendus : 9, 10, 11 pour UE12, 12 pour Moyenne
                if len(notes) > 11:
                    record["UE12_HDM"] = notes[9]
                    record["UE12_ANATOMIE"] = notes[10]
                    record["UE12_TOTAL"] = notes[11]
                if len(notes) > 12:
                    record["MOYENNE_MMOK"] = notes[12]
            
            elif filiere == "PHARMA":
                # PHARMA : Après UE10 (index 8), on attend UE13 (1 note) + Moyenne (1 note)
                # Indices attendus : 9 pour UE13, 10 pour Moyenne
                if len(notes) > 9:
                    record["UE13"] = notes[9]
                if len(notes) > 10:
                    record["MOYENNE_PHARMA"] = notes[10]

            students.append(record)

    return students

def test_parser_logic():
    # --- TEST 1 : Nettoyage des valeurs classiques ---
    assert clean_value("12,10") == 12.10
    assert clean_value("Note sur 20 08,73") == 8.73
    assert clean_value(" 15.023 ") == 15.023
    # --- TEST 2 : Gestion des absences et erreurs PDF ---
    assert clean_value("DEF") == "DNF"
    assert clean_value("ABS") == "DNF"
    assert clean_value("#VALEUR!") == "DNF"     
    # --- TEST 3 : Extraction numérique avec texte parasite ---
    assert clean_value("8.801 MMOK") == 8.801
    assert clean_value("14,693\nMMOK") == 14.693
    line_mmok = "22213290 12.10 10.70 ... MMOK"
    assert ("MMOK" in line_mmok.upper()) is True
    
    print("✅ Tous les tests de logique de base sont passés !")


def save_to_mysql(students):
    print(f"Connexion à {DB_CONFIG['host']}...")
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()

    test_parser_logic()

    try:

        # 1. Semestre
        sql_semestre = """
            INSERT INTO pass_semestres (code_semestre, nom_semestre, annee, date_examen)
            VALUES (%s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE id_semestre=LAST_INSERT_ID(id_semestre);
        """
        cursor.execute(sql_semestre, (CURRENT_SEMESTRE['code'], CURRENT_SEMESTRE['nom'], CURRENT_SEMESTRE['annee'], CURRENT_SEMESTRE['date']))
        id_semestre = cursor.lastrowid
        if id_semestre == 0:
            cursor.execute("SELECT id_semestre FROM pass_semestres WHERE code_semestre = %s", (CURRENT_SEMESTRE['code'],))
            id_semestre = cursor.fetchone()[0]

        # 2. Requêtes d'insertion
        sql_etudiant = """
            INSERT INTO pass_etudiants (id_etudiant, moyenne_mmok, moyenne_pharma, est_absent, filiere)
            VALUES (%s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE 
                moyenne_mmok=VALUES(moyenne_mmok), 
                moyenne_pharma=VALUES(moyenne_pharma),
                est_absent=VALUES(est_absent),
                filiere=VALUES(filiere);
        """

        sql_resultats = """
            INSERT INTO pass_resultats (
                id_etudiant, id_semestre, 
                ue1, ue2, ue3, ue8, ue10, ue13,
                ue7_ca, ue7_cg, ue7_co, ue7_total,
                ue12_hdm, ue12_anatomie, ue12_total
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                ue1=VALUES(ue1), ue2=VALUES(ue2), ue3=VALUES(ue3), 
                ue7_ca=VALUES(ue7_ca), ue7_cg=VALUES(ue7_cg), ue7_co=VALUES(ue7_co), ue7_total=VALUES(ue7_total),
                ue8=VALUES(ue8), ue10=VALUES(ue10), ue13=VALUES(ue13),
                ue12_hdm=VALUES(ue12_hdm), ue12_anatomie=VALUES(ue12_anatomie), ue12_total=VALUES(ue12_total);
        """

        count = 0
        for s in students:
            def fix(v): return None if v == "DNF" else v
            
            # Détection absentéisme (si moyenne est DNF ou manquante)
            moyenne = s['MOYENNE_MMOK'] if s['filiere'] == "MMOK" else s['MOYENNE_PHARMA']
            est_absent = 1 if moyenne in [None, "DNF"] else 0

            # Insert Etudiant
            cursor.execute(sql_etudiant, (
                s['id_etudiant'], 
                fix(s['MOYENNE_MMOK']), 
                fix(s['MOYENNE_PHARMA']), 
                est_absent,
                s['filiere']
            ))

            # Insert Resultats
            cursor.execute(sql_resultats, (
                s['id_etudiant'], id_semestre,
                fix(s['UE1']), fix(s['UE2']), fix(s['UE3']), 
                fix(s['UE8']), fix(s['UE10']), fix(s['UE13']),
                fix(s['UE7_CA']), fix(s['UE7_CG']), fix(s['UE7_CO']), fix(s['UE7_TOTAL']),
                fix(s['UE12_HDM']), fix(s['UE12_ANATOMIE']), fix(s['UE12_TOTAL'])
            ))
            count += 1

        conn.commit()
        print(f"✅ {count} étudiants importés avec succès (MMOK & Pharma séparés).")

    except Exception as e:
        print(f"❌ Erreur SQL: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    data = parse_pdf()
    if data:
        # Debug : afficher un étudiant de chaque type pour vérifier avant envoi
        print("\n--- TEST: Exemple MMOK ---")
        mmok_ex = next((s for s in data if s['filiere'] == 'MMOK'), None)
        if mmok_ex: print(mmok_ex)
        
        print("\n--- TEST: Exemple PHARMA ---")
        pharma_ex = next((s for s in data if s['filiere'] == 'PHARMA'), None)
        if pharma_ex: print(pharma_ex)

        save_to_mysql(data)