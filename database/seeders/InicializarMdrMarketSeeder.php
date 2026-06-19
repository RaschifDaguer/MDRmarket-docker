<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Comerciante;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Repartidor;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InicializarMdrMarketSeeder extends Seeder
{
    use WithoutModelEvents;

    // ── Santa Cruz de la Sierra reference coordinates ─────────────────────────
    // Comercio 1 – TechStore SCZ (Equipetrol, zona norte)
    private const LAT_TECHSTORE = -17.7745;
    private const LNG_TECHSTORE = -63.1935;

    // Comercio 2 – Sabor Cruceño (Centro/Casco Viejo)
    private const LAT_SABOR = -17.7859;
    private const LNG_SABOR = -63.1816;

    // Comercio 3 – FarmaSuper (Av. Beni / 2do Anillo Sur)
    private const LAT_FARMA = -17.7696;
    private const LNG_FARMA = -63.1658;

    // ─────────────────────────────────────────────────────────────────────────
    public function run(): void
    {
        $this->seedCategorias();

        // Each helper returns plain integer User IDs (= role-table PKs).
        // We never rely on Eloquent model instances after create() because
        // Comerciante/Repartidor/Cliente have non-auto-increment PKs and
        // Eloquent's lastInsertId() would return 0.
        [$comId1, $comId2, $comId3] = $this->seedComerciantes();
        [$repId1, $repId2, $repId3] = $this->seedRepartidores();
        [$clId1,  $clId2]           = $this->seedClientes();

        $this->seedProductosTechStore($comId1);
        $this->seedProductosSaborCruceno($comId2);
        $this->seedProductosFarmaSuper($comId3);

        $this->seedTransacciones($comId1, $comId2, $comId3, $repId1, $repId2, $repId3, $clId1, $clId2);
    }

    // ── Categorías ────────────────────────────────────────────────────────────
    private function seedCategorias(): void
    {
        $categorias = [
            ['nombre' => '🍔 Alimentos y Bebidas',          'descripcion' => 'Comida, snacks y bebidas'],
            ['nombre' => '💊 Salud y Belleza',               'descripcion' => 'Cuidado personal, salud y cosmética'],
            ['nombre' => '💻 Tecnología',                    'descripcion' => 'Electrónica, gadgets y accesorios tech'],
            ['nombre' => '🏠 Hogar, Decoración y Muebles',  'descripcion' => 'Muebles, decoración y artículos del hogar'],
            ['nombre' => '👕 Moda y Accesorios',             'descripcion' => 'Ropa, calzado y complementos'],
            ['nombre' => '🏋️ Deportes y Outdoor',           'descripcion' => 'Deportes, fitness y actividades al aire libre'],
            ['nombre' => '🚗 Automotriz',                    'descripcion' => 'Accesorios y repuestos para vehículos'],
            ['nombre' => '🐾 Mascotas',                      'descripcion' => 'Productos para mascotas y su cuidado'],
            ['nombre' => '🎮 Entretenimiento',               'descripcion' => 'Juegos, ocio y entretenimiento'],
            ['nombre' => '🏢 Oficina y Papelería',           'descripcion' => 'Suministros de oficina y papelería'],
            ['nombre' => '👶 Bebés y Niños',                 'descripcion' => 'Productos para bebés y niños'],
            ['nombre' => '🔨 Ferretería, Construcción y Jardín', 'descripcion' => 'Herramientas, construcción y jardinería'],
        ];

        foreach ($categorias as $cat) {
            Categoria::updateOrCreate(['nombre' => $cat['nombre']], $cat);
        }

        Categoria::whereNotIn('nombre', array_column($categorias, 'nombre'))->delete();
    }

    // ── Comerciantes — returns [int $id1, int $id2, int $id3] ────────────────
    private function seedComerciantes(): array
    {
        $data = [
            [
                'email'           => 'techstore@mdrmarket.local',
                'name'            => 'TechStore SCZ',
                'nombre'          => 'TechStore SCZ',
                'telefono'        => '59177001001',
                'direccion'       => 'Av. San Martín 453, Equipetrol, Santa Cruz',
                'descripcion'     => 'Tu tienda de tecnología y gadgets en el corazón de Equipetrol. Envíos rápidos a toda la ciudad.',
                'latitud'         => self::LAT_TECHSTORE,
                'longitud'        => self::LNG_TECHSTORE,
                'foto_perfil_url' => 'https://placehold.co/150/1a237e/ffffff?text=Tech',
                'banner_url'      => 'https://placehold.co/600x200/1a237e/ffffff?text=TechStore+SCZ',
            ],
            [
                'email'           => 'saborcruceno@mdrmarket.local',
                'name'            => 'Sabor Cruceño',
                'nombre'          => 'Sabor Cruceño',
                'telefono'        => '59177002002',
                'direccion'       => 'Calle Junín 320, Centro Histórico, Santa Cruz',
                'descripcion'     => 'Auténtica gastronomía cruceña. Salteñas, empanadas, platos tradicionales con ingredientes frescos.',
                'latitud'         => self::LAT_SABOR,
                'longitud'        => self::LNG_SABOR,
                'foto_perfil_url' => 'https://placehold.co/150/e65100/ffffff?text=Sabor',
                'banner_url'      => 'https://placehold.co/600x200/e65100/ffffff?text=Sabor+Cruce%C3%B1o',
            ],
            [
                'email'           => 'farmasuper@mdrmarket.local',
                'name'            => 'FarmaSuper',
                'nombre'          => 'FarmaSuper',
                'telefono'        => '59177003003',
                'direccion'       => 'Av. Beni 1560, 2do Anillo, Santa Cruz',
                'descripcion'     => 'Farmacia y supermercado. Medicamentos, cuidado personal y productos de primera necesidad las 24 horas.',
                'latitud'         => self::LAT_FARMA,
                'longitud'        => self::LNG_FARMA,
                'foto_perfil_url' => 'https://placehold.co/150/1b5e20/ffffff?text=Farma',
                'banner_url'      => 'https://placehold.co/600x200/1b5e20/ffffff?text=FarmaSuper',
            ],
        ];

        $ids = [];
        foreach ($data as $d) {
            $user = User::create([
                'name'     => $d['name'],
                'email'    => $d['email'],
                'password' => Hash::make('password'),
            ]);
            Comerciante::create([
                'id'              => $user->id,
                'nombre'          => $d['nombre'],
                'email'           => $d['email'],
                'telefono'        => $d['telefono'],
                'direccion'       => $d['direccion'],
                'descripcion'     => $d['descripcion'],
                'latitud'         => $d['latitud'],
                'longitud'        => $d['longitud'],
                'foto_perfil_url' => $d['foto_perfil_url'],
                'banner_url'      => $d['banner_url'],
            ]);
            $ids[] = $user->id; // use User ID, not model instance
        }
        return $ids;
    }

    // ── Repartidores — returns [int $id1, int $id2, int $id3] ────────────────
    private function seedRepartidores(): array
    {
        $data = [
            [
                'email'    => 'diego.rep@mdrmarket.local',
                'name'     => 'Diego Rodríguez',
                'nombre'   => 'Diego Rodríguez',
                'telefono' => '59176101001',
                'placa'    => 'SCZ-1234',
                'tipo'     => 'moto',
            ],
            [
                'email'    => 'valentina.rep@mdrmarket.local',
                'name'     => 'Valentina Cruz',
                'nombre'   => 'Valentina Cruz',
                'telefono' => '59176102002',
                'placa'    => 'SCZ-5678',
                'tipo'     => 'moto',
            ],
            [
                'email'    => 'marco.rep@mdrmarket.local',
                'name'     => 'Marco Flores',
                'nombre'   => 'Marco Flores',
                'telefono' => '59176103003',
                'placa'    => 'SCZ-9012',
                'tipo'     => 'bicicleta',
            ],
        ];

        $ids = [];
        foreach ($data as $d) {
            $user = User::create([
                'name'     => $d['name'],
                'email'    => $d['email'],
                'password' => Hash::make('password'),
            ]);
            Repartidor::create([
                'id'       => $user->id,
                'nombre'   => $d['nombre'],
                'email'    => $d['email'],
                'telefono' => $d['telefono'],
                'placa'    => $d['placa'],
                'tipo'     => $d['tipo'],
            ]);
            $ids[] = $user->id;
        }
        return $ids;
    }

    // ── Clientes — returns [int $id1, int $id2, int $id3] ────────────────────
    private function seedClientes(): array
    {
        $data = [
            [
                'email'     => 'ana.cliente@mdrmarket.local',
                'name'      => 'Ana García',
                'nombre'    => 'Ana García',
                'telefono'  => '59175201001',
                'direccion' => 'Calle Bolívar 210, Barrio Equipetrol Norte, Santa Cruz',
            ],
            [
                'email'     => 'carlos.cliente@mdrmarket.local',
                'name'      => 'Carlos López',
                'nombre'    => 'Carlos López',
                'telefono'  => '59175202002',
                'direccion' => 'Av. Cañoto 890, Barrio El Centro, Santa Cruz',
            ],
            [
                'email'     => 'sofia.cliente@mdrmarket.local',
                'name'      => 'Sofía Martínez',
                'nombre'    => 'Sofía Martínez',
                'telefono'  => '59175203003',
                'direccion' => 'Av. Beni 2345, Barrio Mutualista, Santa Cruz',
            ],
        ];

        $ids = [];
        foreach ($data as $d) {
            $user = User::create([
                'name'     => $d['name'],
                'email'    => $d['email'],
                'password' => Hash::make('password'),
            ]);
            Cliente::create([
                'id'        => $user->id,
                'nombre'    => $d['nombre'],
                'email'     => $d['email'],
                'telefono'  => $d['telefono'],
                'direccion' => $d['direccion'],
            ]);
            $ids[] = $user->id;
        }
        return $ids;
    }

    // ── Productos: TechStore SCZ ──────────────────────────────────────────────
    private function seedProductosTechStore(int $comercianteId): void
    {
        $catTech = Categoria::where('nombre', '💻 Tecnología')->value('id');
        $catOfic = Categoria::where('nombre', '🏢 Oficina y Papelería')->value('id');
        $catEntr = Categoria::where('nombre', '🎮 Entretenimiento')->value('id');

        $productos = [
            ['nombre' => 'Auriculares Bluetooth Premium', 'descripcion' => 'Auriculares inalámbricos con cancelación de ruido activa, 30 h de batería y sonido Hi-Fi.',   'precio' => 185.00, 'stock' => 25, 'id_categoria' => $catTech],
            ['nombre' => 'Cable USB-C a USB-A 2m',        'descripcion' => 'Cable de carga rápida y datos USB-C trenzado nylon, compatible con Android y laptops.',         'precio' =>  14.50, 'stock' => 80, 'id_categoria' => $catTech],
            ['nombre' => 'Memoria USB 64 GB USB 3.0',     'descripcion' => 'Memoria flash de alta velocidad (hasta 120 MB/s) con carcasa compacta y resistente.',           'precio' =>  38.00, 'stock' => 60, 'id_categoria' => $catTech],
            ['nombre' => 'Cargador Inalámbrico 15 W',     'descripcion' => 'Pad de carga rápida inalámbrica Qi compatible con iPhone, Samsung y demás.',                    'precio' =>  55.00, 'stock' => 30, 'id_categoria' => $catTech],
            ['nombre' => 'Teclado Mecánico Compacto',     'descripcion' => 'Teclado 75 % con switches rojos, retroiluminación RGB y conectividad USB-C.',                   'precio' => 165.00, 'stock' => 15, 'id_categoria' => $catTech],
            ['nombre' => 'Mouse Inalámbrico Ergonómico',  'descripcion' => 'Mouse vertical inalámbrico DPI ajustable, reduce la fatiga en largas sesiones de trabajo.',     'precio' =>  72.00, 'stock' => 40, 'id_categoria' => $catTech],
            ['nombre' => 'Hub USB-C 7 en 1',              'descripcion' => 'Hub multipuerto con HDMI 4K, USB-A, USB-C PD, lector SD/MicroSD y Ethernet.',                   'precio' =>  95.00, 'stock' => 20, 'id_categoria' => $catOfic],
            ['nombre' => 'Control para PC/Consola',       'descripcion' => 'Control Bluetooth compatible con PC, Android y consolas, vibración dual.',                      'precio' => 120.00, 'stock' => 18, 'id_categoria' => $catEntr],
        ];

        foreach ($productos as $p) {
            $producto = Producto::create(array_merge($p, [
                'id_comerciante'      => $comercianteId,
                'id_imagen_principal' => null,
            ]));
            $this->crearImagenProducto($producto->id, '400x300/1a237e/ffffff', urlencode($p['nombre']));
        }
    }

    // ── Productos: Sabor Cruceño ──────────────────────────────────────────────
    private function seedProductosSaborCruceno(int $comercianteId): void
    {
        $catFood = Categoria::where('nombre', '🍔 Alimentos y Bebidas')->value('id');

        $productos = [
            ['nombre' => 'Salteña de Pollo',         'descripcion' => 'Salteña horneada rellena de pollo jugoso, papa, arveja y huevo.',   'precio' =>  4.50, 'stock' => 100],
            ['nombre' => 'Salteña de Carne',         'descripcion' => 'Salteña tradicional con carne res, papa, arveja y ají.',            'precio' =>  5.00, 'stock' => 100],
            ['nombre' => 'Empanada de Queso',        'descripcion' => 'Empanada frita rellena de queso derretido.',                        'precio' =>  3.50, 'stock' =>  80],
            ['nombre' => 'Arroz con Pollo Criollo',  'descripcion' => 'Arroz graneado con pollo al mojo, yuca frita y ensalada fresca.',   'precio' => 22.00, 'stock' =>  40],
            ['nombre' => 'Hamburguesa Especial',     'descripcion' => 'Hamburguesa artesanal con doble carne, queso y salsa de la casa.',  'precio' => 28.00, 'stock' =>  35],
            ['nombre' => 'Sopa de Maní',             'descripcion' => 'Sopa boliviana de maní con fideos, papa y carne.',                  'precio' => 18.00, 'stock' =>  30],
            ['nombre' => 'Refresco de Mocochinchi',  'descripcion' => 'Bebida tradicional boliviana de durazno. 500 ml.',                  'precio' =>  6.00, 'stock' =>  60],
            ['nombre' => 'Combo Almuerzo Completo',  'descripcion' => 'Sopa + plato principal + refresco. Cambia diariamente.',            'precio' => 38.00, 'stock' =>  20],
        ];

        foreach ($productos as $p) {
            $producto = Producto::create(array_merge($p, [
                'id_comerciante'      => $comercianteId,
                'id_categoria'        => $catFood,
                'id_imagen_principal' => null,
            ]));
            $this->crearImagenProducto($producto->id, '400x300/e65100/ffffff', urlencode($p['nombre']));
        }
    }

    // ── Productos: FarmaSuper ─────────────────────────────────────────────────
    private function seedProductosFarmaSuper(int $comercianteId): void
    {
        $catSalud = Categoria::where('nombre', '💊 Salud y Belleza')->value('id');
        $catFood  = Categoria::where('nombre', '🍔 Alimentos y Bebidas')->value('id');

        $productos = [
            ['nombre' => 'Paracetamol 500 mg (20 comp)',  'descripcion' => 'Analgésico y antipirético. Caja de 20 comprimidos.',                'precio' =>  8.50, 'stock' => 200, 'id_categoria' => $catSalud],
            ['nombre' => 'Ibuprofeno 400 mg (15 comp)',   'descripcion' => 'Antiinflamatorio y analgésico. Caja de 15 comprimidos.',            'precio' => 10.00, 'stock' => 150, 'id_categoria' => $catSalud],
            ['nombre' => 'Alcohol Isopropílico 500 ml',   'descripcion' => 'Alcohol de uso médico al 70 %. Frasco sellado.',                    'precio' => 15.00, 'stock' =>  80, 'id_categoria' => $catSalud],
            ['nombre' => 'Shampoo Anti-Caspa 400 ml',     'descripcion' => 'Control de caspa desde la primera aplicación.',                     'precio' => 22.00, 'stock' =>  60, 'id_categoria' => $catSalud],
            ['nombre' => 'Crema Hidratante Facial 50 g',  'descripcion' => 'Hidratación con ácido hialurónico. Para todo tipo de piel.',        'precio' => 28.00, 'stock' =>  45, 'id_categoria' => $catSalud],
            ['nombre' => 'Jabón Antibacterial x3',        'descripcion' => 'Pack de 3 jabones de 90 g. Protección duradera.',                   'precio' =>  9.00, 'stock' => 120, 'id_categoria' => $catSalud],
            ['nombre' => 'Leche Entera 1 L',              'descripcion' => 'Leche entera pasteurizada local. Entrega el mismo día.',            'precio' =>  9.50, 'stock' =>  90, 'id_categoria' => $catFood],
            ['nombre' => 'Arroz 1 kg',                    'descripcion' => 'Arroz blanco de grano largo, cosecha santa crucera.',               'precio' =>  7.50, 'stock' => 200, 'id_categoria' => $catFood],
            ['nombre' => 'Aceite Vegetal 1 L',            'descripcion' => 'Aceite de girasol refinado, sin colesterol.',                       'precio' => 12.00, 'stock' => 110, 'id_categoria' => $catFood],
            ['nombre' => 'Vitamina C 1000 mg (30 comp)',  'descripcion' => 'Suplemento efervescente. Refuerza el sistema inmunológico.',        'precio' => 18.00, 'stock' =>  70, 'id_categoria' => $catSalud],
        ];

        foreach ($productos as $p) {
            $producto = Producto::create(array_merge($p, [
                'id_comerciante'      => $comercianteId,
                'id_imagen_principal' => null,
            ]));
            $this->crearImagenProducto($producto->id, '400x300/1b5e20/ffffff', urlencode($p['nombre']));
        }
    }

    // ── Transacciones financieras de muestra ──────────────────────────────────
    private function seedTransacciones(
        int $comId1, int $comId2, int $comId3,
        int $repId1, int $repId2, int $repId3,
        int $clId1,  int $clId2
    ): void {
        $now = now();
        $d   = fn (int $dias, int $h = 10, int $m = 0): string =>
            $now->copy()->subDays($dias)->setTime($h, $m)->toDateTimeString();

        $filas = [
            // ── Ingresos: comisiones de plataforma ───────────────────────────
            ['monto' => 42.80, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta TechStore SCZ (Pedido #001)',    'estado_pago' => 'completado', 'comerciante_id' => $comId1, 'fecha_registro' => $d(1, 9, 0)],
            ['monto' => 18.40, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta Sabor Cruceño (Pedido #002)',   'estado_pago' => 'completado', 'comerciante_id' => $comId2, 'fecha_registro' => $d(1, 11, 30)],
            ['monto' => 28.60, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta FarmaSuper (Pedido #003)',       'estado_pago' => 'completado', 'comerciante_id' => $comId3, 'fecha_registro' => $d(1, 14, 0)],
            ['monto' => 56.25, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta TechStore SCZ (Pedido #004)',    'estado_pago' => 'completado', 'comerciante_id' => $comId1, 'fecha_registro' => $d(2, 10, 15)],
            ['monto' => 12.70, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta Sabor Cruceño (Pedido #005)',   'estado_pago' => 'completado', 'comerciante_id' => $comId2, 'fecha_registro' => $d(2, 16, 45)],
            ['monto' => 35.90, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta FarmaSuper (Pedido #006)',       'estado_pago' => 'completado', 'comerciante_id' => $comId3, 'fecha_registro' => $d(3, 8, 20)],
            ['monto' => 19.50, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta TechStore SCZ (Pedido #007)',    'estado_pago' => 'completado', 'comerciante_id' => $comId1, 'fecha_registro' => $d(4, 12, 0)],
            ['monto' => 43.10, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta FarmaSuper (Pedido #008)',       'estado_pago' => 'completado', 'comerciante_id' => $comId3, 'fecha_registro' => $d(5, 15, 30)],
            ['monto' => 67.40, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta TechStore SCZ (Pedido #009)',    'estado_pago' => 'completado', 'comerciante_id' => $comId1, 'fecha_registro' => $d(6, 11, 0)],
            ['monto' => 22.80, 'tipo' => 'ingreso', 'concepto_detalle' => 'Comisión plataforma – Venta Sabor Cruceño (Pedido #010)',   'estado_pago' => 'completado', 'comerciante_id' => $comId2, 'fecha_registro' => $d(7, 13, 10)],

            // ── Ingresos: tarifas de delivery ─────────────────────────────────
            ['monto' => 8.00,  'tipo' => 'ingreso', 'concepto_detalle' => 'Tarifa de envío – Delivery zona Equipetrol (Pedido #001)',   'estado_pago' => 'completado', 'repartidor_id' => $repId1, 'fecha_registro' => $d(1, 9, 10)],
            ['monto' => 6.50,  'tipo' => 'ingreso', 'concepto_detalle' => 'Tarifa de envío – Delivery zona Centro (Pedido #002)',       'estado_pago' => 'completado', 'repartidor_id' => $repId2, 'fecha_registro' => $d(1, 11, 40)],
            ['monto' => 9.00,  'tipo' => 'ingreso', 'concepto_detalle' => 'Tarifa de envío – Delivery zona 2do Anillo (Pedido #003)',   'estado_pago' => 'completado', 'repartidor_id' => $repId3, 'fecha_registro' => $d(1, 14, 10)],
            ['monto' => 7.50,  'tipo' => 'ingreso', 'concepto_detalle' => 'Tarifa de envío – Delivery zona Equipetrol (Pedido #004)',   'estado_pago' => 'completado', 'repartidor_id' => $repId1, 'fecha_registro' => $d(2, 10, 25)],
            ['monto' => 10.00, 'tipo' => 'ingreso', 'concepto_detalle' => 'Tarifa de envío – Delivery zona Mutualista (Pedido #006)',   'estado_pago' => 'completado', 'repartidor_id' => $repId2, 'fecha_registro' => $d(3, 8, 30)],
            ['monto' => 6.00,  'tipo' => 'ingreso', 'concepto_detalle' => 'Tarifa de envío – Delivery zona Centro (Pedido #010)',       'estado_pago' => 'completado', 'repartidor_id' => $repId1, 'fecha_registro' => $d(7, 13, 20)],

            // ── Ingresos: suscripciones de comercios ──────────────────────────
            ['monto' => 150.00, 'tipo' => 'ingreso', 'concepto_detalle' => 'Suscripción mensual – TechStore SCZ (Junio 2026)',          'estado_pago' => 'completado', 'comerciante_id' => $comId1, 'fecha_registro' => $d(15, 9, 0)],
            ['monto' => 150.00, 'tipo' => 'ingreso', 'concepto_detalle' => 'Suscripción mensual – Sabor Cruceño (Junio 2026)',          'estado_pago' => 'completado', 'comerciante_id' => $comId2, 'fecha_registro' => $d(15, 9, 5)],
            ['monto' => 150.00, 'tipo' => 'ingreso', 'concepto_detalle' => 'Suscripción mensual – FarmaSuper (Junio 2026)',             'estado_pago' => 'completado', 'comerciante_id' => $comId3, 'fecha_registro' => $d(15, 9, 10)],
            ['monto' =>  75.00, 'tipo' => 'ingreso', 'concepto_detalle' => 'Membresía Premium cliente – Plan mensual (Junio 2026)',     'estado_pago' => 'completado', 'cliente_id'    => $clId1,   'fecha_registro' => $d(14, 10, 0)],

            // ── Egresos: pagos a repartidores ─────────────────────────────────
            ['monto' => 48.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Liquidación semanal – Diego Rodríguez (semana 23)',          'estado_pago' => 'completado', 'repartidor_id' => $repId1, 'fecha_registro' => $d(3, 17, 0)],
            ['monto' => 35.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Liquidación semanal – Valentina Cruz (semana 23)',           'estado_pago' => 'completado', 'repartidor_id' => $repId2, 'fecha_registro' => $d(3, 17, 15)],
            ['monto' => 22.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Liquidación semanal – Marco Flores (semana 23)',             'estado_pago' => 'completado', 'repartidor_id' => $repId3, 'fecha_registro' => $d(3, 17, 30)],
            ['monto' => 55.50, 'tipo' => 'egreso',  'concepto_detalle' => 'Liquidación semanal – Diego Rodríguez (semana 22)',          'estado_pago' => 'completado', 'repartidor_id' => $repId1, 'fecha_registro' => $d(10, 17, 0)],
            ['monto' => 40.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Liquidación semanal – Valentina Cruz (semana 22)',           'estado_pago' => 'completado', 'repartidor_id' => $repId2, 'fecha_registro' => $d(10, 17, 15)],
            ['monto' => 28.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Liquidación semanal – Marco Flores (semana 22)',             'estado_pago' => 'completado', 'repartidor_id' => $repId3, 'fecha_registro' => $d(10, 17, 30)],

            // ── Egresos: reembolsos a clientes ────────────────────────────────
            ['monto' => 38.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Reembolso – Pedido cancelado #011 (Ana García)',            'estado_pago' => 'completado', 'cliente_id' => $clId1, 'fecha_registro' => $d(5, 15, 0)],
            ['monto' => 22.00, 'tipo' => 'egreso',  'concepto_detalle' => 'Devolución parcial – Producto defectuoso #012 (C. López)',  'estado_pago' => 'completado', 'cliente_id' => $clId2, 'fecha_registro' => $d(8, 10, 0)],

            // ── Egresos: costos operativos ─────────────────────────────────────
            ['monto' => 120.00, 'tipo' => 'egreso', 'concepto_detalle' => 'Costo operativo – Servidor cloud mensual (Junio 2026)',     'estado_pago' => 'completado', 'fecha_registro' => $d(16, 8, 0)],
            ['monto' =>  45.00, 'tipo' => 'egreso', 'concepto_detalle' => 'Costo operativo – API Google Maps (Junio 2026)',            'estado_pago' => 'completado', 'fecha_registro' => $d(16, 8, 5)],
            ['monto' =>  30.00, 'tipo' => 'egreso', 'concepto_detalle' => 'Costo operativo – SMS/notificaciones push (Junio 2026)',    'estado_pago' => 'completado', 'fecha_registro' => $d(16, 8, 10)],
            ['monto' =>  18.50, 'tipo' => 'egreso', 'concepto_detalle' => 'Comisión pasarela de pagos – QR/tarjeta (Junio 2026)',     'estado_pago' => 'completado', 'fecha_registro' => $d(1, 18, 0)],

            // ── Pendientes ────────────────────────────────────────────────────
            ['monto' => 150.00, 'tipo' => 'ingreso', 'concepto_detalle' => 'Suscripción mensual – TechStore SCZ (Julio 2026)',         'estado_pago' => 'pendiente',  'comerciante_id' => $comId1, 'fecha_registro' => $now->copy()->addDays(1)->toDateTimeString()],
        ];

        foreach ($filas as $fila) {
            Transaccion::create($fila);
        }
    }

    // ── Helper: crear imagen placeholder ─────────────────────────────────────
    private function crearImagenProducto(int $productoId, string $colores, string $texto): void
    {
        $url    = "https://placehold.co/{$colores}?text={$texto}";
        $imagen = ProductoImagen::create([
            'id_producto' => $productoId,
            'url'         => $url,
            'principal'   => true,
        ]);
        Producto::where('id', $productoId)->update(['id_imagen_principal' => $imagen->id]);
    }
}
