<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use App\Services\PythonBridgeService;
use App\Services\StreamValidatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct(
        private StreamValidatorService $validator
    ) {}

    public function index(): View
    {
        $country = Country::where('code', 'ug')->first();
        
        $ugandaChannels = collect();
        if ($country) {
            $ugandaChannels = Channel::active()
                ->online()
                ->where('country_id', $country->id)
                ->with(['category'])
                ->inRandomOrder()
                ->limit(20)
                ->get();
        }

        $featured = Channel::active()
            ->online()
            ->hd()
            ->with(['country', 'category'])
            ->inRandomOrder()
            ->limit(12)
            ->get();

        $countryId = $country?->id;
        $internationalChannels = Channel::active()
            ->online()
            ->where(function ($q) use ($countryId) {
                $q->where('country_id', '!=', $countryId)
                  ->orWhereNull('country_id');
            })
            ->with(['country', 'category'])
            ->inRandomOrder()
            ->limit(12)
            ->get();

        $countries = Country::where('is_active', true)
            ->withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('channels_count', 'desc')
            ->limit(30)
            ->get();

        $categories = Category::withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        $sportsCategory = Category::where('slug', 'sports')->first();
        $soccerKeywords = ['football', 'soccer', 'premier league', 'laliga',
            'serie a', 'bundesliga', 'ligue 1', 'champions league',
            'europa league', 'uefa', 'fifa'];

        $soccerChannels = collect();
        if ($sportsCategory) {
            $soccerChannels = Channel::active()
                ->online()
                ->with(['country', 'category'])
                ->where(function ($q) use ($sportsCategory, $soccerKeywords) {
                    $q->where('category_id', $sportsCategory->id);
                    foreach ($soccerKeywords as $kw) {
                        $q->orWhere('name', 'like', "%{$kw}%");
                    }
                })
                ->inRandomOrder()
                ->limit(12)
                ->get();
        }

        $stats = [
            'channels' => $this->validator->getTotalCount(),
            'online' => $this->validator->getOnlineCount(),
            'countries' => $countries->count(),
        ];

        return view('pages.home', compact(
            'ugandaChannels', 'featured', 'internationalChannels', 'soccerChannels', 'countries',
            'categories', 'stats'
        ));
    }

    public function watch(Request $request, string $slug): View
    {
        $channel = Channel::where('slug', $slug)
            ->active()
            ->with(['country', 'category', 'languages'])
            ->firstOrFail();

        $related = Channel::active()
            ->online()
            ->where('country_id', $channel->country_id)
            ->where('id', '!=', $channel->id)
            ->with(['country', 'category'])
            ->inRandomOrder()
            ->limit(12)
            ->get();

        return view('pages.watch', compact('channel', 'related'));
    }

    public function browse(Request $request): View
    {
        $countryCode = $request->get('country', 'ug');
        $categorySlug = $request->get('category');
        $search = $request->get('search');

        $query = Channel::active()
            ->online()
            ->with(['country', 'category']);

        if ($countryCode) {
            $query->byCountry($countryCode);
        }

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        if ($search) {
            $query->search($search);
        }

        $channels = $query->orderBy('is_online', 'desc')->orderBy('name')->paginate(48);

        $countries = Country::where('is_active', true)
            ->withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        $categories = Category::withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        return view('pages.browse', compact(
            'channels', 'countries', 'categories',
            'countryCode', 'categorySlug', 'search'
        ));
    }

    public function uganda(): View
    {
        $country = Country::where('code', 'ug')->first();
        
        $channels = collect();
        if ($country) {
            $channels = Channel::active()
                ->online()
                ->where('country_id', $country->id)
                ->with(['category'])
                ->orderBy('name')
                ->get();
        }

        $categories = $channels->groupBy(fn($c) => $c->category?->name ?? 'Other');

        return view('pages.uganda', compact('channels', 'categories', 'country'));
    }

    public function ugandaWorking(): View
    {
        $country = Country::where('code', 'ug')->first();

        $total = 0;
        $channels = collect();
        if ($country) {
            $total = Channel::active()
                ->where('country_id', $country->id)
                ->count();

            $channels = Channel::active()
                ->online()
                ->whereNotNull('stream_url')
                ->where('stream_url', '!=', '')
                ->where('country_id', $country->id)
                ->with(['category'])
                ->orderBy('name')
                ->get();
        }

        $categories = $channels->groupBy(fn($c) => $c->category?->name ?? 'Other');

        return view('pages.uganda-working', compact('channels', 'categories', 'country', 'total'));
    }

    public function guide(): View
    {
        $country = Country::where('code', 'ug')->first();
        $channels = collect();
        if ($country) {
            $channels = Channel::active()
                ->online()
                ->where('country_id', $country->id)
                ->with(['category'])
                ->orderBy('name')
                ->get();
        }

        $categories = Category::orderBy('name')->get();
        $countries = Country::where('is_active', true)
            ->withCount(['channels' => fn($q) => $q->active()->online()])
            ->having('channels_count', '>', 0)
            ->orderBy('name')
            ->get();

        return view('pages.guide', compact('channels', 'categories', 'countries'));
    }

    public function favorites(): View
    {
        return view('pages.favorites');
    }

    public function sports(Request $request): View
    {
        $league = $request->get('league');
        $countryCode = $request->get('country');
        $search = $request->get('search');

        $sportsCategory = Category::where('slug', 'sports')->first();

        $query = Channel::active()
            ->online()
            ->with(['country', 'category']);

        $leagueKeywords = [
            'premier-league' => ['premier league', 'epl'],
            'laliga' => ['laliga', 'la liga'],
            'serie-a' => ['serie a'],
            'bundesliga' => ['bundesliga'],
            'ligue-1' => ['ligue 1'],
            'uefa' => ['champions league', 'europa league', 'uefa', 'european cup'],
        ];

        if ($league && isset($leagueKeywords[$league])) {
            $query->where(function ($q) use ($leagueKeywords, $league) {
                foreach ($leagueKeywords[$league] as $kw) {
                    $q->orWhere('name', 'like', "%{$kw}%");
                }
            });
        } elseif ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        } else {
            $soccerKeywords = ['football', 'soccer', 'premier league', 'laliga',
                'serie a', 'bundesliga', 'ligue 1', 'champions league',
                'europa league', 'uefa', 'fifa', 'world cup', 'sport',
                'espn', 'sky sport', 'bein sport', 'dazn', 'eurosport'];

            $query->where(function ($q) use ($sportsCategory, $soccerKeywords) {
                if ($sportsCategory) {
                    $q->orWhere('category_id', $sportsCategory->id);
                }
                foreach ($soccerKeywords as $kw) {
                    $q->orWhere('name', 'like', "%{$kw}%");
                }
            });
        }

        if ($countryCode) {
            $query->byCountry($countryCode);
        }

        $channels = $query->orderBy('is_online', 'desc')->orderBy('name')->paginate(48);
        $online = Channel::active()->online()
            ->whereHas('category', fn($q) => $q->where('slug', 'sports'))
            ->count();

        $countriesList = Cache::remember('sports_countries', 3600, fn() =>
            Country::where('is_active', true)
                ->whereHas('channels', fn($q) => $q->active()->online()
                    ->whereHas('category', fn($cq) => $cq->where('slug', 'sports')))
                ->orderBy('name')
                ->get()
        );

        return view('pages.sports', compact('channels', 'online', 'countriesList', 'league', 'search'));
    }

    public function international(Request $request): View
    {
        $countryCode = $request->get('country');
        $categorySlug = $request->get('category');
        $search = $request->get('search');

        $query = Channel::active()
            ->online()
            ->with(['country', 'category']);

        if ($countryCode) {
            $query->byCountry($countryCode);
        }

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        if ($search) {
            $query->search($search);
        }

        $channels = $query->orderBy('is_online', 'desc')->orderBy('name')->paginate(48);
        $online = Channel::active()->online()->count();

        $countriesList = Cache::remember('intl_countries_all', 3600, fn() =>
            Country::where('is_active', true)
                ->withCount(['channels' => fn($q) => $q->active()->online()])
                ->having('channels_count', '>', 0)
                ->orderBy('name')
                ->get()
        );

        $categories = Cache::remember('intl_categories_all', 3600, fn() =>
            Category::withCount(['channels' => fn($q) => $q->active()->online()])
                ->having('channels_count', '>', 0)
                ->orderBy('name')
                ->get()
        );

        return view('pages.international', compact(
            'channels', 'online', 'countriesList', 'categories',
            'countryCode', 'categorySlug', 'search'
        ));
    }

    public function worldcup(): View
    {
        $pythonBridge = app(PythonBridgeService::class);
        $wcData = $pythonBridge->getWorldCupMatches();

        $matches = $wcData['matches'] ?? [];
        $broadcasters = $wcData['broadcasters'] ?? [];

        $featuredCategory = Category::where('slug', 'sports')->first();
        $sportsChannels = collect();
        if ($featuredCategory) {
            $sportsChannels = Channel::active()
                ->online()
                ->where('category_id', $featuredCategory->id)
                ->inRandomOrder()
                ->limit(12)
                ->get();
        }

        return view('pages.worldcup', compact(
            'matches', 'broadcasters', 'sportsChannels'
        ));
    }
}
