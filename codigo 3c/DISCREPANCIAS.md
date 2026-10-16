# 📋 ANÁLISIS DE DISCREPANCIAS: DIAGRAMA VS CÓDIGO

## DIAGRAMA PROCEDIMENTAL ESPECIFICA:

### PClase (Presentación)
**ATRIBUTOS (según diagrama):**
- id: int
- dia: string
- hora_inicio: string
- hora_fin: string
- nClase: NClase

**MÉTODOS (según diagrama):**
- ListarTabla(): void
- Registrar(): void
- Eliminar(): void
- MarcarAsistencia(): void

---

### NClase (Negocio)
**ATRIBUTOS (según diagrama):**
- dClase: DClase
- dAsistencia: DAsistencia

**MÉTODOS (según diagrama):**
- getClases(): PDOStatement
- setClase(datos: array[]): boolean
- eliminarClase(id: int[]): boolean
- registrarAsistencia(estudiante_id, clase_id, p_ol): boolean
- generarCodigoClase(): string

---

### DClase (Datos)
**ATRIBUTOS (según diagrama):**
- id: int
- fecha: string
- hora_inicio: string
- hora_fin: string
- tema: string
- grupo_id: int
- dbHelper: DatabaseHelper

**MÉTODOS (según diagrama):**
- getTabla(): PDOStatement
- guardarClase(): int
- actualizarClase(): int
- borrarClase(): int
- findByGrupo(grupo_id: int[]): PDOStatement

---

### DAsistencia (Datos)
**ATRIBUTOS (según diagrama):**
- id: int
- estado: string
- clase_id: int
- estudiante_id: int
- dbHelper: DatabaseHelper

**MÉTODOS (según diagrama):**
- guardarAsistencia(): int
- actualizarEstado(): int
- obtenerAsistenciaPorClase(clase_id: int[]): PDOStatement
- eliminarAsistencia(id: int[]): int

---

### DatabaseHelper (Datos - Singleton)
**ATRIBUTOS (según diagrama):**
- connection: PDO
- host: string
- port: int
- dbname: string
- username: string
- password: string

**MÉTODOS (según diagrama):**
- getInstance(): DatabaseHelper
- connect(): boolean
- query(sql: string[]): PDOStatement
- insert(table: string, data: array[]): int
- update(table: string, data: array, id: var[]): int
- delete(table: string, id: var[]): int

---

## 🔴 ATRIBUTOS EXTRA EN EL CÓDIGO (NO EN DIAGRAMA):

### PClase - ATRIBUTOS EXTRA:
1. ❌ `$dbHelper` - NO ESTÁ EN DIAGRAMA (el diagrama especifica solo 5 atributos)
2. ❌ `$profesor_codigo` - NO ESTÁ EN DIAGRAMA (el diagrama especifica solo 5 atributos)

### DClase - ✅ TODO CORRECTO
- ✅ Todos los atributos coinciden con el diagrama

### DAsistencia - ✅ TODO CORRECTO
- ✅ Todos los atributos coinciden con el diagrama

### DatabaseHelper - ✅ TODO CORRECTO
- ✅ Todos los atributos coinciden con el diagrama (no tiene static $instance explícitamente en la imagen pero es implícito en Singleton)

---

## 🔴 MÉTODOS EXTRA EN EL CÓDIGO (NO EN DIAGRAMA):

### PClase - ✅ TODO CORRECTO
- ✅ ListarTabla()
- ✅ Registrar()
- ✅ Eliminar()
- ✅ MarcarAsistencia()

### NClase - ✅ TODO CORRECTO
- ✅ Todos los métodos coinciden

### DClase - ✅ TODO CORRECTO
- ✅ Todos los métodos coinciden

### DAsistencia - ✅ TODO CORRECTO
- ✅ Todos los métodos coinciden

### DatabaseHelper - ✅ TODO CORRECTO
- ✅ getInstance(): DatabaseHelper
- ✅ connect(): boolean
- ✅ query(sql: string[]): PDOStatement
- ✅ insert(table: string, data: array[]): int
- ✅ update(table: string, data: array, id: var[]): int
- ✅ delete(table: string, id: var[]): int

---

## RESUMEN DE PROBLEMAS

### ❌ CRÍTICOS - Atributos no documentados en PClase:

1. **$dbHelper** 
   - En código: ✓ Presente
   - En diagrama: ✗ NO APARECE
   - Ubicación: PClase está en línea ~11
   - **ACCIÓN RECOMENDADA**: Remover o agregar al diagrama

2. **$profesor_codigo**
   - En código: ✓ Presente (línea ~12)
   - En diagrama: ✗ NO APARECE
   - **ACCIÓN RECOMENDADA**: Remover o agregar al diagrama

---

## CONCLUSIÓN

**Total de discrepancias encontradas: 2 atributos extra en PClase**

- Todos los MÉTODOS están correctos ✅
- Todos los atributos de DClase, DAsistencia, DatabaseHelper están correctos ✅
- DatabaseHelper tiene todos sus métodos correctos ✅
- **Solo PClase tiene 2 atributos extra: $dbHelper y $profesor_codigo**

