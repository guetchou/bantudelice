@extends('frontend.layouts.app-modern')
@section('title', 'Location de voiture | BantuDelice')

@section('content')
<!-- Hero Section -->
<section style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%); padding: 100px 0 40px; text-align: center; color: white;">
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
            <i class="fas fa-truck-pickup"></i> Location de voiture
        </h1>
        <p style="font-size: 1.125rem; opacity: 0.9;">Louez le véhicule idéal pour vos besoins, à la journée ou plus.</p>
    </div>
</section>

<!-- Main Content -->
<section class="section" style="background: #F9FAFB; padding: 3rem 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
            
            <!-- Filters -->
            <div style="background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 1.5rem; height: fit-content;">
                <h4 style="margin-bottom: 1.5rem; font-weight: 700;">Filtres</h4>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem;">Type de véhicule</label>
                    <select style="width: 100%; padding: 0.75rem; border: 1px solid #E5E7EB; border-radius: 8px;">
                        <option>Tous les types</option>
                        <option>Berline</option>
                        <option>SUV / 4x4</option>
                        <option>Utilitaire</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem;">Budget max / jour</label>
                    <input type="range" min="10000" max="100000" step="5000" style="width: 100%;">
                    <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.75rem; color: #6B7280;">
                        <span>10k F</span>
                        <span>100k F</span>
                    </div>
                </div>

                <button class="btn btn-primary" style="width: 100%;">Appliquer</button>
            </div>

            <!-- Vehicle Catalog -->
            <div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    @forelse($vehicles as $vehicle)
                    <div style="background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; transition: transform 0.3s;" class="vehicle-card">
                        <div style="height: 200px; background: #f3f4f6; position: relative;">
                            @if($vehicle->image)
                                <img src="{{ asset('images/vehicles/' . $vehicle->image) }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #D1D5DB;">
                                    <i class="fas fa-car" style="font-size: 4rem;"></i>
                                </div>
                            @endif
                            <div style="position: absolute; top: 15px; right: 15px; background: white; padding: 5px 12px; border-radius: 50px; font-weight: 700; color: #3B82F6; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                {{ number_format($vehicle->daily_rate, 0, ',', ' ') }} F/jour
                            </div>
                        </div>
                        <div style="padding: 1.5rem;">
                            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">{{ $vehicle->make }} {{ $vehicle->model }}</h4>
                            <div style="display: flex; gap: 1rem; color: #6B7280; font-size: 0.8125rem; margin-bottom: 1rem;">
                                <span><i class="fas fa-couch"></i> {{ $vehicle->seats }} Places</span>
                                <span><i class="fas fa-cog"></i> Auto</span>
                                <span><i class="fas fa-gas-pump"></i> Essence</span>
                            </div>
                            <p style="font-size: 0.875rem; color: #4B5563; margin-bottom: 1.5rem; line-height: 1.5;">
                                {{ \Illuminate\Support\Str::limit($vehicle->description, 80) }}
                            </p>
                            <button class="btn btn-outline-primary" style="width: 100%;" onclick="rentVehicle('{{ $vehicle->uuid }}')">Louer maintenant</button>
                        </div>
                    </div>
                    @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: white; border-radius: 20px;">
                        <i class="fas fa-search" style="font-size: 4rem; color: #E5E7EB; margin-bottom: 1.5rem;"></i>
                        <h4>Aucun véhicule disponible</h4>
                        <p style="color: #6B7280;">Essayez de modifier vos filtres.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .vehicle-card:hover {
        transform: translateY(-5px);
    }
</style>

<script>
    function rentVehicle(vehicleId) {
        // Rediriger vers une page de détails ou ouvrir un modal de dates
        alert('Cette fonctionnalité nécessite la sélection des dates. Redirection vers les détails...');
    }
</script>
@endsection

