<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Database;
use App\Models\Dessaggregation;
use App\Models\Enact;
use App\Models\Indicator;
use App\Models\Project;
use App\Models\Province;
use App\Models\Psychoeducations;
use App\Models\Training;
use App\Traits\AprToolsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AprGeneratorController extends Controller
{
    use AprToolsTrait;

    public function generate(string $projectId, string $databaseId, string $provinceId, string $fromDate, string $toDate)
    {
        $project = Project::with("outcomes.outputs.indicators.dessaggregations")->find($projectId);
        if (!$project) return response()->json(["status" => false, "message" => "No such project in system!"], 404);

        $database = Database::find($databaseId);
        if (!$database) return response()->json(["status" => false, "message" => "No such database in system!"], 404);

        $province = Province::find($provinceId);
        if (!$province) return response()->json(["status" => false, "message" => "No such province in system!"], 404);

        // parse dates (keeps original behaviour if strings empty)
        $from = $fromDate ? Carbon::parse($fromDate) : null;
        $to = $toDate ? Carbon::parse($toDate) : null;

        $projectPrograms = $project->programs()->pluck("id")->toArray();

        $projectIndicators = $this->projectIndicatorsToASpicificDatabase($project, $databaseId);

        $projectIndicatorsWithDessaggregations = $this->projectIndicatorsWithDessaggregations($project, $databaseId);

        $projectOutputs = $this->projectOutputsToASpicificDatabase($project, $databaseId);

        $projectOutcomes = $this->projectOutcomesToASpicificDatabase($project, $databaseId);

        $updatedIndicators = collect();


        if ($database->name == "cd_database") 
        {

            $beneficiaries = Beneficiary::whereHas('programs', function ($query) use ($projectPrograms, $databaseId) {
                $query->whereIn('programs.id', $projectPrograms)
                      ->where('database_program_beneficiary.database_id', $databaseId);
            })
            ->with(['indicators', 'programs'])
            ->get();
        
            foreach ($projectIndicators as $indicator) {
        
                $achieved = 0;
                $subIndicator = Indicator::where('parent_indicator', $indicator->id)->first();
        
                if ($indicator->dessaggregationType === 'indevidual') {
        
                    $achieved = $beneficiaries->filter(fn($b) => $b->indicators->contains('id', $indicator->id))->count();

                    $this->updateDessaggregationsFromBeneficiaries($indicator, $beneficiaries, $provinceId);
        
                    // if ($subIndicator) {
                    //     $subAchieved = $beneficiaries->reduce(function ($total, $b) use ($indicator) {
                    //         if ($b->indicators->contains('id', $indicator->id)) {
                    //             $total += $b->communityDialogueSessions->count();

                    //             $groupDessaggregation = Dessaggregation::where("indicator_id", $indicator->id)
                    //                                                         ->where("description", "# 0f group MHPSS consultations")->first();

                    //             if ($groupDessaggregation) {

                    //                 $groupSessoins = $b->communityDialogueSessions->count();

                    //                 $beforeTarget = $groupDessaggregation->achived_target;

                    //                 $newTarget = $beforeTarget + $groupSessoins;;

                    //                 $groupDessaggregation->achived_target = $newTarget;

                    //                 $groupDessaggregation->save();

                    //             }
                    //         }
                    //         return $total;
                    //     }, 0);
        
                    //     $subIndicator->achived_target = $subAchieved;
                    //     $subIndicator->save();
                    //     $updatedIndicators->push($subIndicator);
                    // }
        
                } elseif ($indicator->dessaggregationType === 'session') {
                    $achieved = $beneficiaries->reduce(function ($total, $b) use ($indicator) {
                        if ($b->indicators->contains('id', $indicator->id)) {
                            $total += $b->communityDialogueSessions->count();

                            $groupDessaggregation = Dessaggregation::where("indicator_id", $indicator->id)
                                                                            ->where("description", "# 0f group MHPSS consultations")->first();

                            if ($groupDessaggregation) {

                                $groupSessoins = $b->communityDialogueSessions->count();

                                $beforeTarget = $groupDessaggregation->achived_target;

                                $newTarget = $beforeTarget + $groupSessoins;;

                                $groupDessaggregation->achived_target = $newTarget;

                                $groupDessaggregation->save();

                            }
                        }
                        return $total;
                    }, 0);
        
                    // if ($subIndicator) {
                    //     $beneficiaryCount = $beneficiaries->filter(fn($b) => $b->indicators->contains('id', $indicator->id))->count();
                    //     $subIndicator->achived_target = $beneficiaryCount;
                    //     $subIndicator->save();
                    //     $this->updateDessaggregationsFromBeneficiaries($indicator, $beneficiaries, $provinceId);
                    //     $updatedIndicators->push($subIndicator);
                    // }
                }
        
                $indicator->achived_target = $achieved;
                $indicator->save();
        
                $updatedIndicators->push($indicator);
            }
        
        }
        
        elseif ($database->name == "main_database_meal_tool") {

            $beneficiaries = Beneficiary::whereHas('programs', function ($query) use ($projectPrograms) {
                $query->whereIn('programs.id', $projectPrograms)
                    ->where('database_program_beneficiary.database_id', 1);
            })
            ->whereHas('mealTools')
            ->with(['mealTools', 'indicators'])
            ->get();

        
            foreach ($projectIndicators as $indicator) {
                $achieved = $beneficiaries->filter(fn($b) => $b->indicators->contains('id', $indicator->id))->count();
        
                $indicator->achived_target = $achieved;
                $indicator->save();

                // NEW: dessaggregation update using beneficiaries (meal tool context)
                $this->updateDessaggregationsFromBeneficiaries($indicator, $beneficiaries, $provinceId);

                $updatedIndicators->push($indicator);
            }
        }
        
        elseif ($database->name == "psychoeducation_database") {

            $psychoeducations = Psychoeducations::whereHas("program", fn($q) => $q->whereIn("program_id", $projectPrograms))->get();

            foreach ($projectIndicators as $indicator) {
                $achieved = $psychoeducations->where('indicator_id', $indicator->id)->count();
                $indicator->achived_target = $achieved;
                $indicator->save();
                $updatedIndicators->push($indicator);

                $this->updateDessaggregationsFromPsycho($indicator, $psychoeducations->where("indicator_id", $indicator->id), $provinceId);
            }

        }
        
        elseif ($database->name == "training_database") {

            $trainings = Training::where('project_id', $projectId)
                ->where('province_id', $provinceId)
                ->with('beneficiaries')
                ->get();
        
            foreach ($projectIndicators as $indicator) {
                $achieved = $trainings->reduce(function ($carry, $training) use ($indicator) {
                    if ($training->indicator_id == $indicator->id) {
                        return $carry + $training->beneficiaries->count();
                    }
                    return $carry;
                }, 0);
        
                $indicator->achived_target = $achieved;
                $indicator->save();
                $updatedIndicators->push($indicator);

                // NEW: dessaggregation update using trainings collection
                $this->updateDessaggregationsFromTrainings($indicator, $trainings, $provinceId);
            }
        }

        elseif  ($database->name == "enact_database") {


            $enacts = Enact::where('project_id', $projectId)
                ->where('province_id', $provinceId)
                ->with('assessments')
                ->get();

            $numberOfAssessmentsPerMonth = array_fill(0, 12, 0);
            $scoresPerMonth = array_fill(0, 12, 0);

            
            foreach ($projectIndicators as $indicator) {
                
                $achieved = 0;

                $enacts->map(function ($enact) use ($indicator, &$numberOfAssessmentsPerMonth, &$scoresPerMonth, &$achieved) {
                    $achieved += $enact->assessments->reduce(function ($carry, $assessment) use ($indicator, &$numberOfAssessmentsPerMonth, &$scoresPerMonth) {
                        if ($assessment->enact->indicator_id == $indicator->id && $assessment->enact->aprIncluded)  {

                            $monthIndex = (int) Carbon::parse($assessment->date)->format("n") - 1;

                            $numberOfAssessmentsPerMonth[$monthIndex]++;

                            $scoresPerMonth[$monthIndex] += $assessment->totalScore;

                            return $carry + 1;
                        }
                        return $carry;
                    }, 0);
                });
        
                $indicator->achived_target = $achieved;
                $indicator->save();
                $updatedIndicators->push($indicator);
                
                $dessaggregations = $indicator->dessaggregations()
                ->where('province_id', $provinceId)
                ->get();

                if ($dessaggregations->isEmpty()) continue;

                $dessaggregations->map(function ($dessaggregation) use (&$numberOfAssessmentsPerMonth, &$scoresPerMonth) {

                    $dessaggregationFromDb = Dessaggregation::where("id", $dessaggregation["id"])->first();

                    if (!$dessaggregationFromDb) return;

                    switch ($dessaggregation->description) {
                        case '# of supervised psychosocial counsellors':

                            $dessaggregationFromDb->months = $numberOfAssessmentsPerMonth;
                            $dessaggregationFromDb->achived_target = (collect(...$numberOfAssessmentsPerMonth))->reduce(function ($carry, $item) {
                                return $carry + $item;
                            }, 0); 
                            $dessaggregationFromDb->save();

                            break;
                        
                        case '# Accumulated score EQUIP (ENACT) Tool':

                            $dessaggregationFromDb->months = $scoresPerMonth;
                            $dessaggregationFromDb->achived_target = collect(...$scoresPerMonth)->reduce(function ($carry, $item) {
                                return $carry + $item;
                            });
                            $dessaggregationFromDb->save();

                            break;
                    }
                });
                
            }

        }
        
        else {

            $beneficiaries = Beneficiary::whereHas('programs', function ($query) use ($projectPrograms, $databaseId) {
                $query->whereIn('programs.id', $projectPrograms)
                    ->where('database_program_beneficiary.database_id', $databaseId);
            })
            ->with(['indicators.sessions', 'programs'])
            ->get();

            $beneficiaries->transform(function ($beneficiary) {
                $beneficiary->indicators->transform(function ($indicator) use ($beneficiary) {
                    $indicator->setRelation(
                        'sessions',
                        $indicator->sessions->where('beneficiary_id', $beneficiary->id)->values()
                    );
                    return $indicator;
                });
                return $beneficiary;
            });


            foreach ($projectIndicators as $indicator) {
                $achieved = 0;

                if ($indicator->dessaggregationType === 'indevidual') {

                    $achieved = $beneficiaries
                        ->filter(fn($b) => $b->indicators->contains('id', $indicator->id) && $b->aprInclude)
                        ->count();

                    $this->updateDessaggregationsFromBeneficiaries($indicator, $beneficiaries, $provinceId);
                
                    $subIndicator = Indicator::where('parent_indicator', $indicator->id)->first();
                
                    if ($subIndicator instanceof Indicator) {
                
                        $subAchieved = $beneficiaries->reduce(function ($total, $b) use ($indicator) {
                            $ind = $b->indicators->firstWhere('id', $indicator->id);
                
                            if ($ind) {
                                $total += $ind->sessions->count();
                            }
                
                            return $total;
                        }, 0);
                
                        $subIndicator->update(['achived_target' => $subAchieved]);
                    }
                
                    // else {
                    //     \Illuminate\Support\Facades\Log::warning("No sub-indicator found for indicator ID: {$indicator->id}");
                    // }
                }
                 elseif ($indicator->dessaggregationType === 'session') {

                    $subIndicator = Indicator::where('parent_indicator', $indicator->id)->first();

                    $achieved = $beneficiaries->reduce(function ($total, $b) use ($indicator, $beneficiaries, $provinceId) {
                        $ind = $b->indicators->firstWhere('id', $indicator->id);
                        if ($ind) {

                            $total += $ind->sessions->count();

                            // $groupDessaggregation = Dessaggregation::where("indicator_id", $indicator->id)
                                                                            // ->where("description", "# 0f group MHPSS consultations")->first();
                            // $individualDessaggregation = Dessaggregation::where("indicator_id", $indicator->id)->where("description", "# 0f indevidual MHPSS consultations")->first();

                            $this->updateDessaggregationsFromBeneficiaries($indicator, $beneficiaries, $provinceId);

                        };
                        return $total;
                    }, 0);

                    if ($subIndicator) {
                        $beneficiaryCount = $beneficiaries->filter(fn($b) => $b->indicators->contains('id', $indicator->id))->count();
                        $subIndicator->update(['achived_target' => $beneficiaryCount]);
                        $subIndicator->save();
                        
                        $this->updateDessaggregationsFromBeneficiaries($subIndicator, $beneficiaries, $provinceId);

                        $updatedIndicators->push($subIndicator);
                    }
                }

                $indicator->achived_target = $achieved;
                $indicator->save();
                $updatedIndicators->push($indicator);
            }
        }

        $isp3s = $projectIndicatorsWithDessaggregations->flatMap(function ($indicator) {
            return $indicator->isp3()->with("indicators")->get();
        });

        $isp3s->map(function ($isp3) {
            $isp3["isp3"] = $isp3->description;

            unset(
                $isp3["description"],
                $isp3["id"],
                $isp3["pivot"],
            );

            $isp3->indicators->map(function ($indicator) {
                unset(
                    $indicator["created_at"],
                    $indicator["updated_at"],
                    $indicator["database_id"],
                    $indicator["description"],
                    $indicator["dessaggregationType"],
                    $indicator["id"],
                    $indicator["status"],
                    $indicator["target"],
                    $indicator["achived_target"],
                    $indicator["output_id"],
                    $indicator["pivot"],
                    $indicator["type_id"],
                    $indicator["parent_indicator"],
                );

                $indicator->dessaggregations->map(function ($dessaggregation) {

                    $dessaggregation["name"] = $dessaggregation["description"];

                    unset(
                        $dessaggregation["achived_target"],
                        $dessaggregation["created_at"],
                        $dessaggregation["updated_at"],
                        $dessaggregation["description"],
                        $dessaggregation["id"],
                        $dessaggregation["indicator_id"],
                        $dessaggregation["province_id"],
                        $dessaggregation["province_id"],
                    );

                    return $dessaggregation;
                });

            
                return $indicator;
            });
        });

        $finalAPR = [
            "impact" => $project->projectGoal,
            "outcomes" => $project->outcomes->map(function ($outcome) use ($updatedIndicators) {
                return [
                    "name" => $outcome->outcome,
                    "outputs" => $outcome->outputs->map(function ($output) use ($updatedIndicators) {
                        return [
                            "name" => $output->output,
                            "indicators" => $output->indicators->map(function ($indicator) {
                                return [
                                    "code" => $indicator->indicatorRef,
                                    "name" => $indicator->indicator,
                                    "isSub" => false,
                                    "disaggregation" => $indicator->dessaggregations->map(function ($d) {
                                        return [
                                            "name" => $d->description,
                                            "target" => $d->target ?? 0,
                                            "months" => $d->months ?? array_fill(0, 12, 0)
                                        ];
                                    })->toArray(),
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            "isp3s" => $isp3s
        ];
        
        return response()->json([
            "status" => true,
            "message" => "Indicators updated successfully.",
            "data" => $finalAPR,
        ], 200);
    }

    public function showSpicificDatabaseApr(string $projectId, string $databaseId, string $provinceId, string $fromDate, string $toDate)
    {
        $project = Project::with("outcomes.outputs.indicators.dessaggregations")->find($projectId);
        if (!$project) return response()->json(["status" => false, "message" => "No such project in system!"], 404);

        $database = Database::find($databaseId);
        if (!$database) return response()->json(["status" => false, "message" => "No such database in system!"], 404);

        $province = Province::find($provinceId);
        if (!$province) return response()->json(["status" => false, "message" => "No such province in system!"], 404);

        $projectIndicators = $this->projectIndicatorsToASpicificDatabase($project, $databaseId);

        $finalAPR = [
            "impact" => $project->projectGoal,
            "outcomes" => $project->outcomes->map(function ($outcome) use ($projectIndicators) {
                $filteredOutputs = $outcome->outputs->filter(function ($output) use ($projectIndicators) {
                    $hasIndicator = $projectIndicators->where('output_id', $output->id)->isNotEmpty();
                    return $hasIndicator;
                });

                if ($filteredOutputs->isEmpty()) {
                    return null;
                }

                return [
                    "name" => $outcome->outcome,
                    "outputs" => $filteredOutputs->map(function ($output) use ($projectIndicators) {
                        $filteredIndicators = $projectIndicators->where('output_id', $output->id);

                        return [
                            "name" => $output->output,
                            "indicators" => $filteredIndicators->map(function ($indicator) {
                                return [
                                    "code" => $indicator->indicatorRef,
                                    "name" => $indicator->indicator,
                                    "isSub" => false,
                                    "disaggregation" => $indicator->dessaggregations->map(function ($d) {
                                        return [
                                            "name" => $d->description,
                                            "target" => $d->target ?? 0,
                                            "months" => $d->months ?? array_fill(0, 12, 0)
                                        ];
                                    })->toArray(),
                                ];
                            })->values()->toArray(),
                        ];
                    })->values()->toArray(),
                ];
            })->filter()->values()->toArray(),
        ];

        return response()->json([
            "status" => true,
            "data" => $finalAPR
        ]);
    }

    /**
     * Update dessaggregation targets based on beneficiaries collection.
     * This covers demographic dessaggregations and session/group splits.
     */
    private function updateDessaggregationsFromBeneficiaries(Indicator $indicator, Collection $beneficiaries, Int $provinceId)
    {

        $dess = $indicator->dessaggregations()->where('province_id', $provinceId)->get();
        if ($dess->isEmpty()) return;

        $groupTotal = 0;
        $individualTotal = 0;

        $demographics = [
            "Of Male (above 18)" => 0,
            "Of Female (above 18)" => 0,
            "of Male adolescents (12 to 17 years old)" => 0,
            "of Female adolescents (12 to 17 years old)" => 0,
            "of Male children (6 to 11 years old)" => 0,
            "of Female children (6 to 11 years old)" => 0,
            "of Male CU5 (boys)" => 0,
            "of Female CU5 (girls)" => 0,
        ];

        $demographicMonthDate = [];
        foreach ($demographics as $key => $val) {
            $demographicMonthDate[$key] = array_fill(0, 12, 0);
        }

        $groupMonthDate = array_fill(0, 12, 0);

        $individualMonthDate = array_fill(0, 12, 0);

        foreach ($beneficiaries as $b) {
            if (!$b->indicators->contains('id', $indicator->id)) continue;

            $ind = $b->indicators->firstWhere('id', $indicator->id);
            if ($ind) {
                $groupTotal += $ind->sessions->whereNotNull('group')->count();
                $individualTotal += $ind->sessions->whereNull('group')->count();

                $sessions = $ind->sessions;
                foreach ($sessions as $s) {
                    if (!$s->date) continue;
                    $monthIndex = (int) Carbon::parse($s->date)->format("n") - 1;

                    if ($s->group !== null) {
                        $groupMonthDate[$monthIndex]++;
                    } else {
                        $individualMonthDate[$monthIndex]++;
                    }
                }
            }

            $age = (int) $b->age;
            $gender = strtolower($b->gender ?? '');
            try {
                $dateOfRegistration = $b->dateOfRegistration ? Carbon::parse($b->dateOfRegistration) : null;
                $monthIndex = $dateOfRegistration ? ((int) $dateOfRegistration->format("n") - 1) : null;
            } catch (\Exception $e) {
                $monthIndex = null;
            }

            if ($gender === 'male' && $age >= 18) {
                $demographics["Of Male (above 18)"]++;
                if ($monthIndex !== null) $demographicMonthDate["Of Male (above 18)"][$monthIndex]++;
            } elseif ($gender === 'female' && $age >= 18) {
                $demographics["Of Female (above 18)"]++;
                if ($monthIndex !== null) $demographicMonthDate["Of Female (above 18)"][$monthIndex]++;
            } elseif ($gender === 'male' && $age >= 12 && $age <= 17) {
                $demographics["of Male adolescents (12 to 17 years old)"]++;
                if ($monthIndex !== null) $demographicMonthDate["of Male adolescents (12 to 17 years old)"][$monthIndex]++;
            } elseif ($gender === 'female' && $age >= 12 && $age <= 17) {
                $demographics["of Female adolescents (12 to 17 years old)"]++;
                if ($monthIndex !== null) $demographicMonthDate["of Female adolescents (12 to 17 years old)"][$monthIndex]++;
            } elseif ($gender === 'male' && $age >= 6 && $age <= 11) {
                $demographics["of Male children (6 to 11 years old)"]++;
                if ($monthIndex !== null) $demographicMonthDate["of Male children (6 to 11 years old)"][$monthIndex]++;
            } elseif ($gender === 'female' && $age >= 6 && $age <= 11) {
                $demographics["of Female children (6 to 11 years old)"]++;
                if ($monthIndex !== null) $demographicMonthDate["of Female children (6 to 11 years old)"][$monthIndex]++;
            } elseif ($gender === 'male' && $age >= 1 && $age <= 5) {
                $demographics["of Male CU5 (boys)"]++;
                if ($monthIndex !== null) $demographicMonthDate["of Male CU5 (boys)"][$monthIndex]++;
            } elseif ($gender === 'female' && $age >= 1 && $age <= 5) {
                $demographics["of Female CU5 (girls)"]++;
                if ($monthIndex !== null) $demographicMonthDate["of Female CU5 (girls)"][$monthIndex]++;
            }
        }


        foreach ($dess as $d) {
            $desc = trim($d->description);

            if (stripos($desc, 'group') !== false) {
                $d->achived_target = $groupTotal;
                $d->months = $groupMonthDate;
            } elseif (stripos($desc, 'indevidual') !== false || stripos($desc, 'individual') !== false) {
                $d->achived_target = $individualTotal;
                $d->months = $individualMonthDate;
            } elseif (array_key_exists($desc, $demographics)) {
                $d->achived_target = $demographics[$desc];
                $d->months = $demographicMonthDate[$desc];
            } else {
                $d->achived_target = $d->achived_target ?? 0;
                $d->months = $d->months ?? array_fill(0, 12, 0);
            }

            $d->save();
        }
    }

    /**
     * Update dessaggregation targets from psychoeducations collection.
     * For simple case: count psychoeducations per dessaggregation description if possible,
     * otherwise set dess->achived_target to total psycho count for indicator.
     */
    private function updateDessaggregationsFromPsycho(Indicator $indicator, $psychoeducations, $provinceId)
    {
        $dess = $indicator->dessaggregations()->where('province_id', $provinceId)->get();

        if ($dess->isEmpty()) return;

        $demographics = [
            "Of Male (above 18)" => 0,
            "Of Female (above 18)" => 0,
            "of Male adolescents (12 to 17 years old)" => 0,
            "of Female adolescents (12 to 17 years old)" => 0,
        ];

        $demographicMonthDate = [];

        foreach ($demographics as $key => $val) {
            $demographicMonthDate[$key] = array_fill(0, 12, 0);
        }


        foreach ($psychoeducations as $psychoeducation) {

            $monthIndex = (int) Carbon::parse($psychoeducation->awarenessDate)->format("n") - 1;


            $numberOfMenAbove18 = 
                ((int) ($psychoeducation->ofMenHostCommunity ?? 0)) +
                ((int) ($psychoeducation->ofMenIdp ?? 0)) +
                ((int) ($psychoeducation->ofMenRefugee ?? 0)) +
                ((int) ($psychoeducation->ofMenReturnee ?? 0));

            $numberOfWomenAbove18 = 
                ((int) ($psychoeducation->ofWomenHostCommunity ?? 0)) +
                ((int) ($psychoeducation->ofWomenIdp ?? 0)) +
                ((int) ($psychoeducation->ofWomenRefugee ?? 0)) +
                ((int) ($psychoeducation->ofWomenReturnee ?? 0));

            $numberOfMenUnder18 = 
                ((int) ($psychoeducation->ofBoyHostCommunity ?? 0)) +
                ((int) ($psychoeducation->ofBoyIdp ?? 0)) +
                ((int) ($psychoeducation->ofBoyRefugee ?? 0)) +
                ((int) ($psychoeducation->ofBoyReturnee ?? 0));

            $numberOfWomenUnder18 = 
                ((int) ($psychoeducation->ofGirlHostCommunity ?? 0)) +
                ((int) ($psychoeducation->ofGirlIdp ?? 0)) +
                ((int) ($psychoeducation->ofGirlRefugee ?? 0)) +
                ((int) ($psychoeducation->ofGirlReturnee ?? 0));



            $demographicMonthDate["Of Male (above 18)"][$monthIndex] += $numberOfMenAbove18;
            $demographicMonthDate["Of Female (above 18)"][$monthIndex] += $numberOfWomenAbove18;
            $demographicMonthDate["of Male adolescents (12 to 17 years old)"][$monthIndex] += $numberOfMenUnder18;
            $demographicMonthDate["of Female adolescents (12 to 17 years old)"][$monthIndex] += $numberOfWomenUnder18;

            $demographics["Of Male (above 18)"] += $numberOfMenAbove18;
            $demographics["Of Female (above 18)"] += $numberOfWomenAbove18;
            $demographics["of Male adolescents (12 to 17 years old)"] += $numberOfMenUnder18;
            $demographics["of Female adolescents (12 to 17 years old)"] += $numberOfWomenUnder18;
        }
            

        foreach ($demographics as $desc => $value) {
            $targetDess = $dess->firstWhere('description', $desc);
            if ($targetDess) {
                $targetDess->achived_target = $value;
                $targetDess->months = $demographicMonthDate[$desc];
                $targetDess->save();
            }
        }
    }

    /**
     * Update dessaggregation targets using trainings collection.
     * We count beneficiaries inside trainings for the indicator.
     */
    private function updateDessaggregationsFromTrainings(Indicator $indicator, $trainings, $provinceId)
    {
        $dess = $indicator->dessaggregations()
            ->where('province_id', $provinceId)
            ->get();

        if ($dess->isEmpty()) return;

        $demographicTargets = [
            "Of Male (above 18)" => 0,
            "Of Female (above 18)" => 0,
            "of Male adolescents (12 to 17 years old)" => 0,
            "of Female adolescents (12 to 17 years old)" => 0,
            "of Male children (6 to 11 years old)" => 0,
            "of Female children (6 to 11 years old)" => 0,
            "of Male CU5 (boys)" => 0,
            "of Female CU5 (girls)" => 0,
        ];

        foreach ($trainings as $training) {
            if ($training->indicator_id != $indicator->id) continue;

            foreach ($training->beneficiaries as $b) {

                if ($b->gender == "male" && $b->age >= 18)
                    $demographicTargets["Of Male (above 18)"]++;

                else if ($b->gender == "female" && $b->age >= 18)
                    $demographicTargets["Of Female (above 18)"]++;

                else if ($b->gender == "male" && ($b->age >= 12 && $b->age <= 17))
                    $demographicTargets["of Male adolescents (12 to 17 years old)"]++;

                else if ($b->gender == "female" && ($b->age >= 12 && $b->age <= 17))
                    $demographicTargets["of Female adolescents (12 to 17 years old)"]++;

                else if ($b->gender == "male" && ($b->age >= 6 && $b->age <= 11))
                    $demographicTargets["of Male children (6 to 11 years old)"]++;

                else if ($b->gender == "female" && ($b->age >= 6 && $b->age <= 11))
                    $demographicTargets["of Female children (6 to 11 years old)"]++;

                else if ($b->gender == "male" && ($b->age >= 1 && $b->age <= 5))
                    $demographicTargets["of Male CU5 (boys)"]++;

                else if ($b->gender == "female" && ($b->age >= 1 && $b->age <= 5))
                    $demographicTargets["of Female CU5 (girls)"]++;
            }
        }

        foreach ($demographicTargets as $desc => $value) {
            $targetDess = $dess->firstWhere('description', $desc);
            if ($targetDess) {
                $targetDess->achived_target = $value;
                $targetDess->save();
            }
        }
    }

}



